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
        // Invalidar códigos anteriores
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

        // Código correcto
        $user->update(['email_verified_at' => now()]);
        $record->delete();

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
