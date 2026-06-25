<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Models\TopMedal;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

final class TopMedalAwardedNotification extends Notification
{
    use Queueable;

    public function __construct(
        private readonly TopMedal $medal,
    ) {}

    public function via($notifiable): array
    {
        $channels = ['database'];

        $settings = $notifiable->notificationSetting;
        if ($settings && $settings->wantsPush()) {
            $channels[] = \App\Channels\PushChannel::class;
        }

        return $channels;
    }

    public function toArray($notifiable): array
    {
        $entityName = $this->resolveEntityName();

        return [
            'type' => 'top_medal_awarded',
            'subject' => 'Medalla Top 100 Lyrium',
            'message_preview' => "Felicidades! Tu {$this->medal->entity_type} \"{$entityName}\" ha recibido la medalla Top 100 Lyrium.",
            'medal_id' => (string) $this->medal->id,
            'entity_type' => $this->medal->entity_type,
            'entity_id' => (string) $this->medal->medalable_id,
            'rank_position' => $this->medal->rank_position,
        ];
    }

    private function resolveEntityName(): string
    {
        $this->medal->loadMissing('medalable');
        $entity = $this->medal->medalable;

        if (! $entity) {
            return 'Desconocido';
        }

        return match ($this->medal->entity_type) {
            'store' => $entity->store_name ?? $entity->trade_name ?? 'Tienda',
            'product' => $entity->name,
            'service' => $entity->name,
            default => 'Entidad',
        };
    }
}
