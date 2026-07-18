<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

final class EnsureUserNotBanned
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user && $user->is_banned) {
            $user->tokens()->delete();

            return response()->json([
                'success' => false,
                'error' => 'Tu cuenta ha sido suspendida. Contacta al soporte para más información.',
                'code' => 'ACCOUNT_SUSPENDED',
            ], 403);
        }

        return $next($request);
    }
}
