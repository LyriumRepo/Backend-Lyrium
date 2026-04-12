<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Models\Store;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

final class EnsureContractActive
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        // Solo aplica a sellers (los admins están exentos)
        if (! $user || $user->hasRole('administrator')) {
            return $next($request);
        }

        $store = Store::where('owner_id', $user->id)->first();

        if (! $store) {
            return response()->json([
                'message' => 'No tienes una tienda registrada.',
            ], 403);
        }

        $hasActiveContract = $store->contracts()->where('status', 'ACTIVE')->exists();

        if (! $hasActiveContract) {
            return response()->json([
                'message' => 'Debes tener un convenio digital activo para realizar esta operación. '.
                             'Descarga el documento desde tu panel, fírmalo y envíalo para verificación.',
                'contract_status' => 'inactive',
            ], 403);
        }

        return $next($request);
    }
}
