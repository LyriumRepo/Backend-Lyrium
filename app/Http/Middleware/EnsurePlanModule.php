<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Services\PlanService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

final class EnsurePlanModule
{
    public function __construct(private readonly PlanService $planService) {}

    public function handle(Request $request, Closure $next, string $capability): Response
    {
        $store = $request->user()?->ownedStores()->first();

        if (! $store) {
            return response()->json([
                'message' => 'No tienes una tienda registrada.',
            ], 404);
        }

        if (! $this->planService->can($store, $capability)) {
            return response()->json([
                'message' => 'Esta funcionalidad no está disponible en tu plan actual.',
                'upgrade_url' => '/seller/planes',
            ], 403);
        }

        return $next($request);
    }
}
