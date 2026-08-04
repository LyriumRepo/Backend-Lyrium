<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Channels\PushChannel;
use App\Models\StoreProfileRequest;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

final class ProfileRequestNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        private readonly StoreProfileRequest $profileRequest
    ) {}

    public function via(object $notifiable): array
    {
        return ['database', PushChannel::class];
    }

    public function toPush(object $notifiable): array
    {
        $store      = $this->profileRequest->store;
        $storeName  = $store?->trade_name ?? $store?->store_name ?? 'una tienda';
        $sellerName = $store?->owner?->name ?? 'Un vendedor';

        return [
            'title' => '✏️ Solicitud de actualización de datos',
            'body'  => "{$sellerName} solicita actualizar el perfil de \"{$storeName}\".",
            'data'  => [
                'type'     => 'profile_request_created',
                'store_id' => (string) ($store?->id ?? ''),
                'url'      => '/admin/sellers?tab=validacion',
            ],
        ];
    }

    public function toArray(object $notifiable): array
    {
        $store = $this->profileRequest->store;

        return [
            'type'               => 'profile_request_created',
            'profile_request_id' => $this->profileRequest->id,
            'store_id'           => $store?->id,
            'store_name'         => $store?->trade_name ?? $store?->store_name ?? 'Tienda desconocida',
            'seller_name'        => $store?->owner?->name ?? 'Vendedor',
            'seller_email'       => $store?->owner?->email,
            'fields_requested'   => array_keys($this->profileRequest->data ?? []),
            'subject'            => ($store?->owner?->name ?? 'Un vendedor') . ' solicitó actualizar el perfil de "' . ($store?->trade_name ?? $store?->store_name ?? 'su tienda') . '"',
        ];
    }
}
