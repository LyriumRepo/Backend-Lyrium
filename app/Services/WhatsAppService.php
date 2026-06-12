<?php

declare(strict_types=1);

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

final class WhatsAppService
{
    private const API_URL = 'https://admin.miapi.cloud/api/whatsapp/send';

    private string $token;

    public function __construct()
    {
        $this->token = config('services.miapicloud.token', '');
    }

    public function sendText(string $phone, string $message): bool
    {
        if (empty($this->token) || empty($phone)) {
            Log::warning('WhatsAppService: token o teléfono vacío', [
                'has_token' => ! empty($this->token),
                'phone' => $phone,
            ]);

            return false;
        }

        $normalizedPhone = $this->normalizePhone($phone);

        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer '.$this->token,
                'Content-Type' => 'application/json',
            ])->timeout(15)->post(self::API_URL, [
                'phone' => $normalizedPhone,
                'message' => $message,
            ]);

            $body = $response->json();

            if (! $response->successful()) {
                Log::error('WhatsAppService: error enviando mensaje', [
                    'phone' => $normalizedPhone,
                    'status' => $response->status(),
                    'response' => $body,
                ]);

                return false;
            }

            Log::info('WhatsAppService: mensaje enviado', [
                'phone' => $normalizedPhone,
                'response' => $body,
            ]);

            return true;
        } catch (\Throwable $e) {
            Log::error('WhatsAppService: excepción', [
                'phone' => $normalizedPhone,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    public function sendBookingReminder(
        string $phone,
        string $customerName,
        string $serviceName,
        string $specialistName,
        string $storeName,
        string $date,
        string $time,
    ): bool {
        $message = "🔔 *Recordatorio de Cita* 🔔\n\n"
            ."Hola {$customerName},\n\n"
            ."Te recordamos que tienes una cita en *{$storeName}*:\n\n"
            ."📅 *Fecha:* {$date}\n"
            ."⏰ *Hora:* {$time}\n"
            ."👨‍⚕️ *Especialista:* {$specialistName}\n"
            ."📋 *Servicio:* {$serviceName}\n\n"
            .'Te esperamos 🚀';

        return $this->sendText($phone, $message);
    }

    private function normalizePhone(string $phone): string
    {
        $clean = preg_replace('/[^0-9]/', '', $phone);

        if (strlen($clean) === 9) {
            return '51'.$clean;
        }

        if (strlen($clean) === 12 && str_starts_with($clean, '51')) {
            return $clean;
        }

        return $clean;
    }
}
