<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use Laravel\Socialite\Contracts\User as SocialiteUser;
use Laravel\Socialite\Facades\Socialite;

final class SocialAuthService
{
    private const SUPPORTED_PROVIDERS = ['google', 'facebook'];

    private const DEFAULT_FRONTEND_URL = 'http://localhost:3000';

    public function isProviderSupported(string $provider): bool
    {
        return in_array($provider, self::SUPPORTED_PROVIDERS, true);
    }

    public function redirect(string $provider, ?string $frontendUrl = null): string
    {
        $state = Str::random(40);
        $frontendCallback = $frontendUrl ?? config('services.oauth.frontend_url', self::DEFAULT_FRONTEND_URL);

        Cache::put("social_oauth_state:{$provider}:{$state}", [
            'frontend_url' => $frontendCallback,
        ], now()->addMinutes(10));

        return Socialite::driver($provider)
            ->stateless()
            ->with(['state' => $state])
            ->redirect()
            ->getTargetUrl();
    }

    /**
     * @return array{social_user: SocialiteUser|null, frontend_url: string}
     */
    public function handleCallback(string $provider, string $code, string $state): array
    {
        $cacheKey = "social_oauth_state:{$provider}:{$state}";
        $cacheData = Cache::pull($cacheKey);

        if (! $cacheData) {
            return [
                'social_user' => null,
                'frontend_url' => config('services.oauth.frontend_url', self::DEFAULT_FRONTEND_URL),
            ];
        }

        $socialUser = Socialite::driver($provider)->stateless()->user();

        return [
            'social_user' => $socialUser,
            'frontend_url' => $cacheData['frontend_url'] ?? self::DEFAULT_FRONTEND_URL,
        ];
    }

    public function findOrCreateUser(SocialiteUser $socialUser, string $provider): array
    {
        $user = User::where('provider', $provider)
            ->where('provider_id', $socialUser->getId())
            ->first();

        if ($user) {
            if ($user->avatar !== $socialUser->getAvatar()) {
                $user->update(['avatar' => $socialUser->getAvatar()]);
            }

            return ['user' => $user, 'is_new_user' => false];
        }

        $user = User::where('email', $socialUser->getEmail())->first();

        if ($user) {
            $user->update([
                'provider' => $provider,
                'provider_id' => $socialUser->getId(),
                'avatar' => $user->avatar ?? $socialUser->getAvatar(),
            ]);

            return ['user' => $user, 'is_new_user' => false];
        }

        $username = $this->generateUniqueUsername(
            $socialUser->getName() ?? $socialUser->getNickname() ?? 'user'
        );

        $user = User::create([
            'name' => $socialUser->getName() ?? 'Usuario',
            'username' => $username,
            'email' => $socialUser->getEmail(),
            'nicename' => Str::slug($socialUser->getName() ?? $username),
            'provider' => $provider,
            'provider_id' => $socialUser->getId(),
            'avatar' => $socialUser->getAvatar(),
            'email_verified_at' => now(),
            'password' => null,
        ]);

        $user->assignRole('customer');

        return ['user' => $user, 'is_new_user' => true];
    }

    private function generateUniqueUsername(string $name): string
    {
        $username = Str::slug($name, '_');
        $baseUsername = $username;
        $counter = 1;

        while (User::where('username', $username)->exists()) {
            $username = $baseUsername.'_'.$counter++;
        }

        return $username;
    }
}
