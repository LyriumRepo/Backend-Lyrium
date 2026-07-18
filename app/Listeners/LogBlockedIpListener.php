<?php

declare(strict_types=1);

namespace App\Listeners;

use App\Events\AuditLogCreated;
use App\Models\AuditLog;
use App\Models\BlockedIp;
use Illuminate\Support\Facades\Log;

final class LogBlockedIpListener
{
    public function handle(AuditLogCreated $event): void
    {
        $log = $event->auditLog;

        if (! str_starts_with($log->event, 'security.ip.')) {
            return;
        }

        $ip = $log->ip_address;

        if ($ip === null) {
            return;
        }

        try {
            match ($log->event) {
                'security.ip.blocked' => $this->block($log, $ip),
                'security.ip.unblocked' => $this->unblock($log, $ip),
                'security.ip.flagged' => $this->flag($log, $ip),
                'security.ip.whitelisted' => $this->whitelist($log, $ip),
                default => null,
            };
        } catch (\Throwable $e) {
            Log::error('LogBlockedIpListener: fallo al procesar evento IP', [
                'event' => $log->event,
                'ip' => $ip,
                'error' => $e->getMessage(),
            ]);
        }
    }

    private function block(AuditLog $log, string $ip): void
    {
        $existing = BlockedIp::byIp($ip)->first();

        BlockedIp::updateOrCreate(
            ['ip_address' => $ip],
            [
                'reason' => $existing?->reason ?? $log->description,
                'blocked_by' => $existing?->blocked_by ?? $log->user_id,
                'blocked_at' => $existing?->blocked_at ?? ($log->created_at ?? now()),
                'expires_at' => $this->extractExpiresAt($log),
                'unblocked_at' => null,
                'unblocked_by' => null,
                'status' => BlockedIp::STATUS_BLOCKED,
            ],
        );
    }

    private function unblock(AuditLog $log, string $ip): void
    {
        BlockedIp::where('ip_address', $ip)
            ->whereIn('status', [BlockedIp::STATUS_BLOCKED, BlockedIp::STATUS_FLAGGED])
            ->update([
                'unblocked_at' => $log->created_at ?? now(),
                'unblocked_by' => $log->user_id,
                'status' => BlockedIp::STATUS_UNBLOCKED,
            ]);
    }

    private function flag(AuditLog $log, string $ip): void
    {
        BlockedIp::updateOrCreate(
            ['ip_address' => $ip],
            [
                'reason' => $log->description,
                'blocked_by' => $log->user_id,
                'blocked_at' => $log->created_at ?? now(),
                'status' => BlockedIp::STATUS_FLAGGED,
            ],
        );
    }

    private function whitelist(AuditLog $log, string $ip): void
    {
        BlockedIp::updateOrCreate(
            ['ip_address' => $ip],
            [
                'reason' => $log->description,
                'blocked_by' => $log->user_id,
                'blocked_at' => $log->created_at ?? now(),
                'status' => BlockedIp::STATUS_WHITELISTED,
            ],
        );
    }

    private function extractExpiresAt(AuditLog $log): ?string
    {
        $metadata = $log->metadata;

        if (is_array($metadata) && isset($metadata['expires_at'])) {
            return $metadata['expires_at'];
        }

        return null;
    }
}
