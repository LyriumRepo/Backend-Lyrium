<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Support\ClientIp;
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

            DB::table('sessions')->updateOrInsert(
                ['user_id' => $user->id],
                [
                    'id' => (string) Str::uuid(),
                    'user_id' => $user->id,
                    'token_id' => $tokenId,
                    'ip_address' => ClientIp::resolve($request),
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
