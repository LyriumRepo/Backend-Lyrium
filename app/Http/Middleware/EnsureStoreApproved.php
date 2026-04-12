<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

final class EnsureStoreApproved
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user || ! $user->hasRole('seller')) {
            return response()->json(['message' => 'Se requiere cuenta de vendedor.'], 403);
        }

        $store = $user->store;

        if (! $store || ! $store->isApproved()) {
            return response()->json(['message' => 'Tu tienda no está aprobada.'], 403);
        }

        return $next($request);
    }
}
