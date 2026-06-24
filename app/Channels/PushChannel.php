<?php

declare(strict_types=1);

namespace App\Channels;

use App\Models\UserDevice;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

final class PushChannel
{
    private ?string $accessToken = null;
    private ?int $tokenExpiresAt = null;

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

        $projectId = config('services.fcm.project_id');

        if (!$projectId) {
            Log::warning('[PushChannel] FCM_PROJECT_ID no configurado.');
            return;
        }

        foreach ($devices as $device) {
            try {
                $token = $this->getAccessToken();

                if (!$token) {
                    continue;
                }

                $response = Http::withToken($token)
                    ->post("https://fcm.googleapis.com/v1/projects/{$projectId}/messages:send", [
                        'message' => [
                            'token' => $device->fcm_token,
                            'notification' => [
                                'title' => $title,
                                'body' => $body,
                            ],
                            'data' => collect($data)->merge([
                                'title' => $title,
                                'body' => $body,
                            ])->map(fn ($v) => (string) $v)->toArray(),
                        ],
                    ]);

                if ($response->successful()) {
                    $device->touch();
                } elseif ($response->status() === 404) {
                    $errorBody = $response->json();
                    $errorMsg = $errorBody['error']['message'] ?? '';

                    if (str_contains($errorMsg, 'UNREGISTERED') || str_contains($errorMsg, 'NOT_FOUND')) {
                        $device->delete();
                        Log::info('[PushChannel] Token obsoleto eliminado.', [
                            'device_id' => $device->id,
                            'error' => $errorMsg,
                        ]);
                    }
                } else {
                    Log::warning('[PushChannel] Error en respuesta FCM v1', [
                        'device_id' => $device->id,
                        'status' => $response->status(),
                        'body' => $response->body(),
                    ]);
                }
            } catch (\Throwable $e) {
                Log::error('[PushChannel] Error enviando push', [
                    'device_id' => $device->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }
    }

    private function getAccessToken(): ?string
    {
        if ($this->accessToken && $this->tokenExpiresAt && now()->timestamp < $this->tokenExpiresAt) {
            return $this->accessToken;
        }

        try {
            $credentials = $this->resolveCredentials();

            if (!$credentials) {
                return null;
            }

            $token = $this->fetchOAuthToken($credentials);

            if (!$token) {
                return null;
            }

            $this->accessToken = $token['access_token'];
            $this->tokenExpiresAt = now()->timestamp + ($token['expires_in'] ?? 3600) - 60;

            return $this->accessToken;
        } catch (\Throwable $e) {
            Log::error('[PushChannel] Error obteniendo token OAuth2', [
                'error' => $e->getMessage(),
            ]);

            return null;
        }
    }

    private function fetchOAuthToken(array $credentials): ?array
    {
        $now = now()->timestamp;
        $privateKey = $credentials['private_key'];
        $clientEmail = $credentials['client_email'];
        $scope = 'https://www.googleapis.com/auth/firebase.messaging';

        $header = $this->base64urlEncode(json_encode([
            'alg' => 'RS256',
            'typ' => 'JWT',
        ]));

        $payload = $this->base64urlEncode(json_encode([
            'iss' => $clientEmail,
            'scope' => $scope,
            'aud' => 'https://oauth2.googleapis.com/token',
            'iat' => $now,
            'exp' => $now + 3600,
        ]));

        $signature = '';
        openssl_sign(
            "{$header}.{$payload}",
            $signature,
            $privateKey,
            'sha256WithRSAEncryption'
        );

        $jwt = "{$header}.{$payload}." . $this->base64urlEncode($signature);

        $response = Http::asForm()->post('https://oauth2.googleapis.com/token', [
            'grant_type' => 'urn:ietf:params:oauth:grant-type:jwt-bearer',
            'assertion' => $jwt,
        ]);

        if (!$response->successful()) {
            Log::warning('[PushChannel] Error al intercambiar JWT por token OAuth2', [
                'status' => $response->status(),
                'body' => $response->body(),
            ]);

            return null;
        }

        return $response->json();
    }

    private function resolveCredentials(): ?array
    {
        $json = config('services.fcm.credentials_json');

        if ($json) {
            $decoded = json_decode(base64_decode($json), true);

            if (is_array($decoded)) {
                return $decoded;
            }

            Log::warning('[PushChannel] FCM_CREDENTIALS_JSON no es un JSON válido.');
        }

        $path = config('services.fcm.credentials_path');

        if ($path && file_exists($path)) {
            $decoded = json_decode(file_get_contents($path), true);

            if (is_array($decoded)) {
                return $decoded;
            }

            Log::warning('[PushChannel] FCM_CREDENTIALS_PATH no contiene un JSON válido.');
        }

        Log::warning('[PushChannel] No hay credenciales de servicio FCM configuradas. Se necesita FCM_CREDENTIALS_JSON o FCM_CREDENTIALS_PATH.');

        return null;
    }

    private function base64urlEncode(string $data): string
    {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }
}
