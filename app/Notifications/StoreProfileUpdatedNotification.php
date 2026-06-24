<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Models\Store;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

final class StoreProfileUpdatedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;
    public int $backoff = 60;

    public function __construct(
        private readonly Store $store,
        private readonly string $changeType = 'general',
    ) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        $labels = [
            'general'  => 'datos generales',
            'visual'   => 'identidad visual',
            'branches' => 'sucursales',
            'logo'     => 'logo',
            'banner'   => 'banner',
            'gallery'  => 'galería de imágenes',
        ];

        $label = $labels[$this->changeType] ?? 'información';

        return [
            'type'       => 'store_profile_updated',
            'title'      => "✏️ Tienda actualizada: {$this->store->trade_name}",
            'message'    => "El vendedor actualizó los {$label} de su tienda \"{$this->store->trade_name}\".",
            'store_id'   => $this->store->id,
            'store_name' => $this->store->trade_name,
            'change_type' => $this->changeType,
            'action_url' => '/admin/stores/' . $this->store->id,
            'subject'    => "✏️ {$this->store->trade_name} actualizó sus {$label}",
        ];
    }
}
