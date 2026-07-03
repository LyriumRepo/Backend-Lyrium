<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Services\AuditService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

final class AuditAuthMiddleware
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
        $status = $response->getStatusCode();
        $isSuccess = $status >= 200 && $status < 300;

        $event = $this->resolveEvent($uri, $method, $status);

        if ($event === null) {
            return;
        }

        $success = match ($event) {
            'auth.login.success', 'auth.logout', 'auth.token.refreshed' => true,
            'auth.login.failed' => false,
            default => $isSuccess,
        };

        $this->auditService->record(
            event: $event,
            module: 'auth',
            description: $this->resolveDescription($event, $request),
            success: $success,
            source: AuditService::SOURCE_WEB,
            metadata: [
                'http_method' => $method,
                'http_status' => $status,
            ],
        );
    }

    private function resolveEvent(string $uri, string $method, int $status): ?string
    {
        $uri = '/' . trim($uri, '/');

        return match (true) {
            $method === 'POST' && str_contains($uri, '/auth/login') && $status >= 200 && $status < 300 => 'auth.login.success',
            $method === 'POST' && str_contains($uri, '/auth/login') && $status >= 400 => 'auth.login.failed',
            $method === 'POST' && str_contains($uri, '/auth/logout') => 'auth.logout',
            $method === 'POST' && str_contains($uri, '/auth/refresh') => 'auth.token.refreshed',
            default => null,
        };
    }

    private function resolveDescription(string $event, Request $request): string
    {
        return match ($event) {
            'auth.login.success' => 'Inicio de sesión exitoso',
            'auth.login.failed' => 'Intento de inicio de sesión fallido',
            'auth.logout' => 'Cierre de sesión',
            'auth.token.refreshed' => 'Token de acceso renovado',
            default => "Evento de autenticación: {$event}",
        };
    }
}
