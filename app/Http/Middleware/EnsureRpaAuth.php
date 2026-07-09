<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

final class EnsureRpaAuth
{
    public function handle(Request $request, Closure $next): Response
    {
        $secret = $request->header('X-Internal-RPA-Secret');

        $expected = config('app.internal_rpa_secret');

        if (! $secret || $secret !== $expected) {
            return response()->json(['error' => 'No autorizado.'], 401);
        }

        return $next($request);
    }
}