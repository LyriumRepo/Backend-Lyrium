<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

final class GoogleAuthService
{
    /**
     * Verifica el ID token de Google y retorna los datos del usuario.
     */
    public function verifyToken(string $credential): ?array
    {
        $response = Http::get('https://oauth2.googleapis.com/tokeninfo', [
            'id_token' => $credential,
        ]);

        if (! $response->ok()) {
            return null;
        }

        $payload = $response->json();

        // Verificar que el token es para nuestra app
        if ($payload['aud'] !== config('services.google.client_id')) {
            return null;
        }

        return [
            'google_id' => $payload['sub'],
            'email' => $payload['email'],
            'name' => $payload['name'] ?? $payload['email'],
            'avatar' => $payload['picture'] ?? null,
            'email_verified' => ($payload['email_verified'] ?? 'false') === 'true',
        ];
    }

    /**
     * Encuentra o crea un usuario a partir de los datos de Google.
     */
    public function findOrCreateUser(array $googleData): array
    {
        // 1. Buscar por google_id
        $user = User::where('google_id', $googleData['google_id'])->first();

        if ($user) {
            return ['user' => $user, 'is_new_user' => false];
        }

        // 2. Buscar por email (usuario existente que se vincula con Google)
        $user = User::where('email', $googleData['email'])->first();

        if ($user) {
            $user->update([
                'google_id' => $googleData['google_id'],
                'avatar' => $user->avatar ?? $googleData['avatar'],
                'email_verified_at' => $user->email_verified_at ?? now(),
            ]);

            return ['user' => $user, 'is_new_user' => false];
        }

        // 3. Crear usuario nuevo
        $username = Str::slug($googleData['name'], '_');
        $baseUsername = $username;
        $counter = 1;
        while (User::where('username', $username)->exists()) {
            $username = $baseUsername.'_'.$counter++;
        }

        $user = User::create([
            'name' => $googleData['name'],
            'username' => $username,
            'email' => $googleData['email'],
            'nicename' => Str::slug($googleData['name']),
            'google_id' => $googleData['google_id'],
            'avatar' => $googleData['avatar'],
            'email_verified_at' => $googleData['email_verified'] ? now() : null,
            'password' => null,
        ]);

        $user->assignRole('customer');

        return ['user' => $user, 'is_new_user' => true];
    }
}
