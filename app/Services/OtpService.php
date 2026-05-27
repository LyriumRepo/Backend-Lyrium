<?php

declare(strict_types=1);

namespace App\Services;

use App\Mail\OtpVerificationMail;
use App\Models\EmailVerificationCode;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;

final class OtpService
{
    private const CODE_LENGTH = 6;

    private const EXPIRY_MINUTES = 10;

    private const MAX_ATTEMPTS = 5;

    private const RESEND_COOLDOWN_SECONDS = 60;

    public function generate(User $user): void
    {
        // Invalidar códigos anteriores del usuario
        EmailVerificationCode::where('user_id', $user->id)->delete();

        $plainCode = str_pad((string) random_int(0, 999999), self::CODE_LENGTH, '0', STR_PAD_LEFT);

        EmailVerificationCode::create([
            'user_id' => $user->id,
            'code' => Hash::make($plainCode),
            'expires_at' => now()->addMinutes(self::EXPIRY_MINUTES),
            'attempts' => 0,
        ]);

        Mail::to($user->email)->queue(
            new OtpVerificationMail($plainCode, $user->name)
        );
    }

    /**
     * Verifica el código OTP y actualiza el estado de verificación de correo del usuario.
     * (Usado principalmente para el registro o logins bloqueados)
     */
    public function verify(User $user, string $code): array
    {
        $record = EmailVerificationCode::where('user_id', $user->id)
            ->latest()
            ->first();

        if (! $record) {
            return ['success' => false, 'error' => 'No hay código de verificación pendiente.'];
        }

        if ($record->isExpired()) {
            $record->delete();

            return ['success' => false, 'error' => 'El código ha expirado. Solicita uno nuevo.'];
        }

        if ($record->hasExceededAttempts(self::MAX_ATTEMPTS)) {
            $record->delete();

            return ['success' => false, 'error' => 'Demasiados intentos. Solicita un nuevo código.'];
        }

        if (! Hash::check($code, $record->code)) {
            $record->increment('attempts');
            $remaining = self::MAX_ATTEMPTS - $record->fresh()->attempts;

            return ['success' => false, 'error' => "Código incorrecto. Te quedan {$remaining} intentos."];
        }

        // Código correcto - Marcar correo como verificado
        $user->update(['email_verified_at' => now()]);
        $record->delete();

        return ['success' => true];
    }

    /**
     * Verifica de forma única el código OTP sin modificar 'email_verified_at'.
     * Permite reintentos asincrónicos (hasta 3 fallos) antes de borrar el registro.
     * (Especialmente diseñado para el modal de 2FA operativo)
     */
    public function verifyOnly(User $user, string $code): array
    {
        // Traemos todos los códigos pendientes de este usuario (del más nuevo al más viejo)
        $records = EmailVerificationCode::where('user_id', $user->id)
            ->latest()
            ->get();

        if ($records->isEmpty()) {
            return ['success' => false, 'error' => 'No hay código de verificación pendiente.'];
        }

        // Usamos el registro más reciente para verificar límites de intentos y expiración global
        $latestRecord = $records->first();

        if ($latestRecord->isExpired()) {
            EmailVerificationCode::where('user_id', $user->id)->delete();
            return ['success' => false, 'error' => 'El código de seguridad ha expirado.'];
        }

        if ($latestRecord->attempts >= 3) {
            EmailVerificationCode::where('user_id', $user->id)->delete();
            return ['success' => false, 'error' => 'Muchos intentos fallidos. Solicita un nuevo código de seguridad.'];
        }

        // Buscamos si el código ingresado coincide con alguno de los hashes válidos generados
        $validRecord = null;
        foreach ($records as $record) {
            if (Hash::check($code, $record->code)) {
                $validRecord = $record;
                break;
            }
        }

        // Si no coincide con ningún registro, incrementamos los intentos fallidos
        if (! $validRecord) {
            $latestRecord->increment('attempts');
            $attemptsMade = $latestRecord->fresh()->attempts;

            if ($attemptsMade >= 3) {
                EmailVerificationCode::where('user_id', $user->id)->delete();
                return ['success' => false, 'error' => 'Muchos intentos fallidos. Se ha bloqueado el código de seguridad.'];
            }

            $remaining = 3 - $attemptsMade;
            return [
                'success' => false,
                'error' => "Código incorrecto. Te quedan {$remaining} " . ($remaining === 1 ? 'intento' : 'intentos') . "."
            ];
        }

        // Si coincide, limpiamos de inmediato de la base de datos todos los códigos acumulados del usuario
        EmailVerificationCode::where('user_id', $user->id)->delete();

        return ['success' => true];
    }

    public function canResend(User $user): bool
    {
        $lastCode = EmailVerificationCode::where('user_id', $user->id)
            ->latest()
            ->first();

        if (! $lastCode) {
            return true;
        }

        return $lastCode->created_at->diffInSeconds(now()) >= self::RESEND_COOLDOWN_SECONDS;
    }
}
