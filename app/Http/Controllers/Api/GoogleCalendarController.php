<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Google\Client as GoogleClient;
use Google\Service\Calendar;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * GoogleCalendarController
 *
 * Maneja el flujo OAuth2 para que un vendedor conecte su Google Calendar.
 * Integrado óptimamente con el prefijo "google" en api.php.
 */
final class GoogleCalendarController extends Controller
{
    // ── Status ────────────────────────────────────────────────────────────────

    /**
     * Retorna si la tienda del vendedor tiene Google Calendar conectado.
     *
     * GET /api/google/status
     */
    public function status(Request $request): JsonResponse
    {
        $store = $request->user()
            ->stores()
            ->where('status', 'approved')
            ->first();

        if (! $store) {
            return $this->error('No tienes una tienda aprobada', 404);
        }

        return $this->success([
            'connected'   => ! is_null($store->google_calendar_token),
            'calendar_id' => $store->google_calendar_id,
            'store_name'  => $store->trade_name ?? $store->store_name,
        ]);
    }

    // ── Iniciar OAuth ─────────────────────────────────────────────────────────

    /**
     * Genera la URL de autorización de Google para iniciar el flujo OAuth2.
     *
     * GET /api/google/auth-url
     */
    public function authUrl(Request $request): JsonResponse
    {
        $store = $request->user()
            ->stores()
            ->where('status', 'approved')
            ->first();

        if (! $store) {
            return $this->error('No tienes una tienda aprobada', 403);
        }

        $client = $this->makeClient();

        // Codificar user_id + store_id en el state para recuperarlos de forma segura en el callback público
        $state = base64_encode(json_encode([
            'user_id'  => $request->user()->id,
            'store_id' => $store->id,
        ]));

        $client->setState($state);

        return $this->success(['url' => $client->createAuthUrl()]);
    }

    // ── Callback OAuth ────────────────────────────────────────────────────────

    /**
     * Google redirige aquí con el authorization code (Endpoint Público).
     * Intercambia el code por tokens y los persiste en la tienda.
     *
     * GET /api/google/callback?code=...&state=...
     */
    public function callback(Request $request): RedirectResponse
    {
        $frontendBase = config('app.frontend_url', 'http://localhost:3000');
        $errorRedirect = $frontendBase . '/seller/services?calendar=error';

        // Validar parámetros provenientes de la API de Google
        $code  = $request->query('code');
        $state = $request->query('state');

        if (! $code || ! $state) {
            return redirect($errorRedirect . '&reason=missing_params');
        }

        // Decodificar el state para reasociar la sesión al usuario y tienda correctos
        $stateData = json_decode(base64_decode($state), true);
        if (! isset($stateData['user_id'], $stateData['store_id'])) {
            return redirect($errorRedirect . '&reason=invalid_state');
        }

        $user  = User::find($stateData['user_id']);
        $store = $user?->stores()->find($stateData['store_id']);

        if (! $user || ! $store) {
            return redirect($errorRedirect . '&reason=not_found');
        }

        try {
            $client = $this->makeClient();

            // Intercambiar el código temporal por los tokens de larga duración
            $token = $client->fetchAccessTokenWithAuthCode($code);

            if (isset($token['error'])) {
                return redirect($errorRedirect . '&reason=' . $token['error']);
            }

            $client->setAccessToken($token);

            // Obtener el ID del calendario principal de la cuenta Google asociada (ej: email)
            $calendarService = new Calendar($client);
            $primaryCalendar = $calendarService->calendars->get('primary');
            $calendarId      = $primaryCalendar->getId();

            // Persistir de forma segura en la tienda del Biomarketplace
            $store->update([
                'google_calendar_token' => json_encode($token),
                'google_calendar_id'    => $calendarId,
            ]);

            return redirect($frontendBase . '/seller/services?calendar=connected');
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::error('GoogleCalendar callback error', [
                'store_id' => $store->id ?? null,
                'error'    => $e->getMessage(),
            ]);

            return redirect($errorRedirect . '&reason=exception');
        }
    }

    // ── Desconectar ───────────────────────────────────────────────────────────

    /**
     * Elimina el token y el calendar_id de la tienda.
     *
     * DELETE /api/google/disconnect
     */
    public function disconnect(Request $request): JsonResponse
    {
        $store = $request->user()
            ->stores()
            ->where('status', 'approved')
            ->firstOrFail();

        // Revocar el token directamente en los servidores de Google para mayor seguridad
        if ($store->google_calendar_token) {
            try {
                $client = $this->makeClient();
                $client->setAccessToken(json_decode($store->google_calendar_token, true));
                $client->revokeToken();
            } catch (\Throwable) {
                // Continuar borrado local aunque falle la revocación remota
            }
        }

        $store->update([
            'google_calendar_token' => null,
            'google_calendar_id'    => null,
        ]);

        return $this->success(['disconnected' => true]);
    }

    // ── Helper privado ────────────────────────────────────────────────────────

    private function makeClient(): GoogleClient
    {
        $client = new GoogleClient();
        $client->setClientId(config('services.google.client_id'));
        $client->setClientSecret(config('services.google.client_secret'));

        // Sincronizar dinámicamente con la nueva ruta prefijada de Google
        $client->setRedirectUri(url('/api/google/callback'));

        $client->addScope(Calendar::CALENDAR);
        $client->setAccessType('offline');
        $client->setPrompt('consent'); // Forzar consentimiento para asegurar refresh_token
        $client->setIncludeGrantedScopes(true);

        return $client;
    }
}
