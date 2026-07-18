<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Catalogs\SecurityEvents;
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

        $uri = '/' . trim($request->path(), '/');
        $method = $request->method();
        $status = $response->getStatusCode();
        $isSuccess = $status >= 200 && $status < 300;

        $event = $this->resolveEvent($uri, $method, $status);

        if ($event === null) {
            return;
        }

        $route = $request->route();
        $routeParams = $route ? $route->parameters() : [];

        $this->auditService->record(
            event: $event,
            module: 'security',
            description: $this->resolveDescription($event, $method, $routeParams),
            success: $isSuccess,
            source: AuditService::SOURCE_WEB,
            metadata: [
                'http_method' => $method,
                'http_status' => $status,
                'route_params' => $routeParams,
            ],
        );
    }

    private function resolveEvent(string $uri, string $method, int $status): ?string
    {
        $isSuccess = $status >= 200 && $status < 300;

        return match (true) {
            str_contains($uri, 'security/ip-blocked') || str_contains($uri, 'security/ips') && $method === 'POST' && $isSuccess => SecurityEvents::IP_BLOCKED,
            str_contains($uri, 'security/ips') && $method === 'PUT' && $isSuccess => SecurityEvents::IP_UNBLOCKED,
            str_contains($uri, 'security/ips') && $method === 'DELETE' && $isSuccess => SecurityEvents::IP_UNBLOCKED,
            str_contains($uri, 'security/alerts') && $method === 'PUT' && str_contains($uri, 'dismiss') && $isSuccess => SecurityEvents::ALERT_DISMISSED,
            str_contains($uri, 'security/alerts') && $method === 'PUT' && str_contains($uri, 'resolve') && $isSuccess => SecurityEvents::ALERT_RESOLVED,
            str_contains($uri, 'security/protection') && $method === 'POST' && $isSuccess => SecurityEvents::PROTECTION_RULE_CREATED,
            str_contains($uri, 'security/protection') && $method === 'PUT' && $isSuccess => SecurityEvents::PROTECTION_RULE_UPDATED,
            str_contains($uri, 'security/protection') && $method === 'DELETE' && $isSuccess => SecurityEvents::PROTECTION_RULE_DELETED,
            str_contains($uri, 'security/settings') && $method === 'PUT' && $isSuccess => SecurityEvents::AUDIT_SETTINGS_CHANGED,
            default => null,
        };
    }

    private function resolveDescription(string $event, string $method, array $params): string
    {
        return match ($event) {
            SecurityEvents::IP_BLOCKED => 'IP bloqueada desde el panel de seguridad',
            SecurityEvents::IP_UNBLOCKED => 'IP desbloqueada desde el panel de seguridad',
            SecurityEvents::ALERT_DISMISSED => 'Alerta de seguridad descartada',
            SecurityEvents::ALERT_RESOLVED => 'Alerta de seguridad resuelta',
            SecurityEvents::PROTECTION_RULE_CREATED => 'Regla de protección creada',
            SecurityEvents::PROTECTION_RULE_UPDATED => 'Regla de protección actualizada',
            SecurityEvents::PROTECTION_RULE_DELETED => 'Regla de protección eliminada',
            SecurityEvents::AUDIT_SETTINGS_CHANGED => 'Configuración de auditoría modificada',
            default => "Evento de seguridad: {$event}",
        };
    }
}
