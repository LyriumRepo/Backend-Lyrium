<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Services\AuditService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

final class AuditSecurityMiddleware
{
    public function __construct(
        private readonly AuditService $auditService,
    ) {}

    public function handle(Request $request, Closure $next): Response
    {
        return $next($request);
    }

    public function terminate(Request $request, Response $response): void
    {
        if ($request->attributes->get('_audit_recorded')) {
            return;
        }

        $route = $request->route();
        $uri = $request->path();
        $method = $request->method();

        // Placeholder for future security CRUD events
        // Phase 7 will add specific event resolution here
    }
}
