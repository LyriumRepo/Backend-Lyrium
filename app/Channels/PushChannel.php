<?php

declare(strict_types=1);

namespace App\Channels;

use App\Models\UserDevice;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

final class PushChannel
{
    public function send(object $notifiable, Notification $notification): void
    {
        $payload = $notification->toPush($notifiable);
        $title = $payload['title'] ?? 'Notificación';
        $body = $payload['body'] ?? '';
        $data = $payload['data'] ?? [];

        $devices = UserDevice::where('user_id', $notifiable->id)->get();

        if ($devices->isEmpty()) {
            Log::info('[PushChannel] Sin dispositivos registrados para el usuario.', [
                'user_id' => $notifiable->id ?? '?',
            ]);
            return;
        }

        $serverKey = config('services.fcm.server_key');

        if (!$serverKey) {
            Log::warning('[PushChannel] FCM_SERVER_KEY no configurado.');
            return;
        }

        foreach ($devices as $device) {
            try {
                $response = Http::withHeaders([
                    'Authorization' => 'key=' . $serverKey,
                    'Content-Type' => 'application/json',
                ])->post(config('services.fcm.api_url'), [
                    'to' => $device->fcm_token,
                    'notification' => [
                        'title' => $title,
                        'body' => $body,
                        'sound' => 'default',
                    ],
                    'data' => array_merge($data, [
                        'title' => $title,
                        'body' => $body,
                    ]),
                    'priority' => 'high',
                ]);

                if ($response->successful()) {
                    $result = $response->json();
                    if (isset($result['results'][0]['error'])) {
                        $error = $result['results'][0]['error'];
                        if (in_array($error, ['NotRegistered', 'InvalidRegistration'])) {
                            $device->delete();
                            Log::info('[PushChannel] Token obsoleto eliminado.', [
                                'device_id' => $device->id,
                                'error' => $error,
                            ]);
                        }
                    }
                }

                $device->touch();
            } catch (\Throwable $e) {
                Log::error('[PushChannel] Error enviando push', [
                    'device_id' => $device->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }
    }
}
