<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\UserResource;
use App\Services\SocialAuthService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

final class SocialAuthController extends Controller
{
    public function __construct(
        private readonly SocialAuthService $socialAuthService,
    ) {}

    /**
     * GET /api/auth/social/{provider}
     * Returns the OAuth redirect URL.
     * Optional query param: ?frontend_url=https://yourfrontend.com
     * If provided, the state will redirect to that URL after OAuth.
     */
    public function redirect(Request $request, string $provider): JsonResponse
    {
        if (! $this->socialAuthService->isProviderSupported($provider)) {
            return $this->errorWithCode('INVALID_PROVIDER', 'Provider no soportado', 404);
        }

        $frontendUrl = $request->query('frontend_url');
        $redirectUrl = $this->socialAuthService->redirect($provider, $frontendUrl);

        return $this->success(['redirect_url' => $redirectUrl]);
    }

    /**
     * GET /api/auth/social/{provider}/callback
     * Handles the OAuth callback from the provider.
     * Returns JSON with token + user (for frontend API route).
     */
    public function callback(Request $request, string $provider): JsonResponse|RedirectResponse
    {
        if (! $this->socialAuthService->isProviderSupported($provider)) {
            return $this->errorWithCode('INVALID_PROVIDER', 'Provider no soportado', 404);
        }

        if ($request->has('error')) {
            $description = $request->get('error_description', 'OAuth error');

            return $this->handleOAuthError($request, $description);
        }

        $code = $request->get('code');
        $state = $request->get('state');

        if (! $code || ! $state) {
            return $this->handleOAuthError($request, 'Código o estado faltante.');
        }

        try {
            $result = $this->socialAuthService->handleCallback($provider, $code, $state);

            if (! $result['social_user']) {
                return $this->handleOAuthError($request, 'Estado de OAuth inválido o expirado.');
            }

            $authResult = $this->socialAuthService->findOrCreateUser($result['social_user'], $provider);
            $user = $authResult['user'];

            $user->tokens()->delete();
            $token = $user->createToken('social-'.$provider)->plainTextToken;

            $userData = new UserResource($user);
            $frontendUrl = $result['frontend_url'];

            $redirectTo = $frontendUrl.'/auth/callback?'.http_build_query([
                'token' => $token,
                'token_type' => 'Bearer',
                'user' => json_encode($userData->toArray($request)),
            ]);

            return $this->success([
                'redirect_to' => $redirectTo,
                'token' => $token,
                'token_type' => 'Bearer',
                'user' => $userData,
            ]);
        } catch (\Exception $e) {
            return $this->handleOAuthError(
                $request,
                'No se pudo completar la autenticación con '.ucfirst($provider)
            );
        }
    }

    private function handleOAuthError(Request $request, string $message): JsonResponse|RedirectResponse
    {
        $frontendUrl = config('services.oauth.frontend_url', 'http://localhost:3000');
        $frontendCallback = $request->query('frontend_url', $frontendUrl);

        if ($request->wantsJson()) {
            return $this->errorWithCode('OAUTH_ERROR', $message, 400);
        }

        return redirect()->to($frontendCallback.'/auth/callback?error='.urlencode($message));
    }
}
