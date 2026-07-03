<?php

declare(strict_types=1);

namespace App\Events;

use App\Models\AuditLog;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

final class RepeatedFailedLoginEvent
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public readonly AuditLog $auditLog,
        public readonly string $ipAddress,
        public readonly int $attempts,
    ) {}
}
