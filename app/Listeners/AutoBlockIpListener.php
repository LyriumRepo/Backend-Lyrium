<?php

declare(strict_types=1);

namespace App\Listeners;

use App\Events\RepeatedFailedLoginEvent;
use App\Models\AuditLog;
use App\Models\BlockedIp;
use App\Models\SecurityAlert;
use App\Models\SystemConfig;
use App\Services\AuditService;
use Illuminate\Support\Facades\Log;

final class AutoBlockIpListener
{
    public function __construct(
        private readonly AuditService $auditService,
    ) {}

    public function handle(RepeatedFailedLoginEvent $event): void
    {
        $ip = $event->ipAddress;
        $attempts = $event->attempts;

        $alreadyBlocked = BlockedIp::active()->byIp($ip)->exists();

        if ($alreadyBlocked) {
            return;
        }

        $windowMinutes = (int) SystemConfig::getByKey('autoblock_window_minutes', config('audit.patterns.failed_login.window_minutes', 10));
        $baseDuration = (int) SystemConfig::getByKey('autoblock_duration_minutes', 20);

        $previousBlocks = AuditLog::where('event', 'security.ip.blocked')
            ->where('ip_address', $ip)
            ->count();

        $expiresAt = match (true) {
            $baseDuration === 0 => null,
            $previousBlocks >= 2 => now()->addMinutes(min($baseDuration * 3, 1440)),
            $previousBlocks >= 1 => now()->addMinutes(min($baseDuration * 1.5, 120)),
            default => now()->addMinutes($baseDuration),
        };

        BlockedIp::create([
            'ip_address' => $ip,
            'reason' => "Auto-bloqueo tras {$attempts} intentos fallidos ({$previousBlocks} bloqueos previos)",
            'status' => BlockedIp::STATUS_BLOCKED,
            'expires_at' => $expiresAt,
            'created_at' => now(),
        ]);

        SecurityAlert::create([
            'type' => 'auto_block_ip',
            'severity' => 'high',
            'title' => "IP bloqueada automáticamente: {$ip}",
            'message' => "IP {$ip} bloqueada tras {$attempts} intentos fallidos (bloqueo #{$previousBlocks})",
            'ip_address' => $ip,
            'status' => SecurityAlert::STATUS_OPEN,
            'created_at' => now(),
        ]);

        try {
            $this->auditService->record(
                event: 'security.ip.blocked',
                module: 'security',
                description: "IP {$ip} bloqueada automáticamente tras {$attempts} intentos fallidos de inicio de sesión.",
                severity: 'critical',
                success: false,
                source: AuditService::SOURCE_SYSTEM,
                metadata: [
                    'failed_attempts' => $attempts,
                    'window_minutes' => $windowMinutes,
                    'expires_at' => $expiresAt->toISOString(),
                    'auto_blocked' => true,
                ],
            );

            Log::info("IP bloqueada automáticamente por intentos fallidos", [
                'ip' => $ip,
                'attempts' => $attempts,
                'expires_at' => $expiresAt->toISOString(),
            ]);
        } catch (\Throwable $e) {
            Log::error('AutoBlockIpListener: fallo al bloquear IP automáticamente', [
                'ip' => $ip,
                'attempts' => $attempts,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
