<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Models\Service;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

final class ServiceStatusNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        private readonly Service $service,
        private readonly string $newStatus,
        private readonly ?string $reason = null,
    ) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        return [
            'service_id' => $this->service->id,
            'service_name' => $this->service->name,
            'service_status' => $this->newStatus,
            'reason' => $this->reason,
            'type' => 'service_status_changed',
        ];
    }
}
