<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Models\BlockedIp;
use App\Services\AuditService;
use App\Support\ClientIp;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

final class SecurityAccessMiddleware
{
    public function __construct(
        private readonly AuditService $auditService,
    ) {}

    public function handle(Request $request, Closure $next): Response
    {
        $ip = ClientIp::resolve($request);

        if ($ip === null) {
            return $next($request);
        }

        $record = BlockedIp::byIp($ip)->first();

        if ($record === null) {
            return $next($request);
        }

        if ($record->status === BlockedIp::STATUS_WHITELISTED) {
            $request->attributes->set('_ip_whitelisted', true);

            return $next($request);
        }

        if ($record->status !== BlockedIp::STATUS_BLOCKED) {
            return $next($request);
        }

        if ($record->expires_at !== null && $record->expires_at->isPast()) {
            return $next($request);
        }

        $this->auditService->record(
            event: 'security.ip.blocked',
            module: 'security',
            description: "Request bloqueado desde IP {$ip} — motivo: {$record->reason}",
            severity: 'critical',
            success: false,
            source: AuditService::SOURCE_WEB,
            metadata: [
                'blocked_ip_id' => $record->id,
                'reason' => $record->reason,
                'request_url' => $request->fullUrl(),
                'http_method' => $request->method(),
            ],
        );

        return response()->json([
            'success' => false,
            'error' => 'Acceso denegado. Su IP ha sido bloqueada por razones de seguridad.',
            'code' => 'IP_BLOCKED',
        ], 403);
    }
}
