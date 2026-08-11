<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Laravel\Sanctum\PersonalAccessToken;

final class TrackAdminSession
{
    public function handle(Request $request, Closure $next)
    {
        $user = $request->user();

        if ($user) {
            $now = now()->timestamp;
            $token = $user->currentAccessToken();
            $tokenId = $token instanceof PersonalAccessToken ? $token->id : null;

            // TEMPORAL: diagnostico del bug "misma IP para todas las sesiones".
            // Quitar una vez identificada la causa (ver conversacion 2026-08-11).
            \Illuminate\Support\Facades\Log::warning('DIAG session ip', [
                'user_id' => $user->id,
                'request_ip' => $request->ip(),
                'ips_chain' => $request->ips(),
                'remote_addr' => $request->server('REMOTE_ADDR'),
                'x_forwarded_for' => $request->header('X-Forwarded-For'),
                'cf_connecting_ip' => $request->header('CF-Connecting-IP'),
                'cf_ipcountry' => $request->header('CF-IPCountry'),
                'cf_ray' => $request->header('CF-Ray'),
            ]);

            DB::table('sessions')->updateOrInsert(
                ['user_id' => $user->id],
                [
                    'id' => (string) Str::uuid(),
                    'user_id' => $user->id,
                    'token_id' => $tokenId,
                    'ip_address' => $request->ip(),
                    'user_agent' => $request->userAgent(),
                    'country' => $request->header('CF-IPCountry'),
                    'last_activity' => $now,
                    'payload' => '[]',
                ]
            );
        }

        return $next($request);
    }
}
