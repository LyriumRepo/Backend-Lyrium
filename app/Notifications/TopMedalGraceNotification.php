<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Models\TopMedal;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

final class TopMedalGraceNotification extends Notification
{
    use Queueable;

    private const GRACE_DAYS = 30;

    public function __construct(
        private readonly TopMedal $medal,
    ) {}

    public function via($notifiable): array
    {
        $channels = ['database'];

        $settings = $notifiable->notificationSetting;
        if ($settings && $settings->wantsPush()) {
            $channels[] = 'push';
        }

        return $channels;
    }

    public function toArray($notifiable): array
    {
        $entityName = $this->resolveEntityName();

        return [
            'type' => 'top_medal_grace',
            'subject' => 'Medalla Top 100 — Periodo de gracia',
            'message_preview' => "Tu {$this->medal->entity_type} \"{$entityName}\" ha salido del Top 100. Tienes {$this->getGraceDays()} días para recuperar tu posicion.",
            'medal_id' => (string) $this->medal->id,
            'entity_type' => $this->medal->entity_type,
            'entity_id' => (string) $this->medal->medalable_id,
            'grace_ends_at' => $this->medal->grace_ends_at?->toISOString(),
        ];
    }

    private function getGraceDays(): int
    {
        return self::GRACE_DAYS;
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
