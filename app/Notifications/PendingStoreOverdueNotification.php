<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Models\Store;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

final class PendingStoreOverdueNotification extends Notification
{
    use Queueable;

    public function __construct(
        private readonly int $overdueCount,
        private readonly Store $oldestStore,
    ) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type'          => 'pending_stores_overdue',
            'title'         => "⚠ {$this->overdueCount} tienda(s) pendientes de aprobación por más de 72 horas",
            'message'       => "Hay {$this->overdueCount} tienda(s) pendientes que superaron el SLA de 72 horas. La más antigua es \"{$this->oldestStore->trade_name}\" (registrada el {$this->oldestStore->created_at->format('d/m/Y H:i')}).",
            'overdue_count' => $this->overdueCount,
            'oldest_store'  => [
                'id'         => $this->oldestStore->id,
                'trade_name' => $this->oldestStore->trade_name,
                'ruc'        => $this->oldestStore->ruc,
                'created_at' => $this->oldestStore->created_at->toIso8601String(),
            ],
            'action_url' => '/admin/stores?status=pending',
        ];
    }
}
