<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Models\StoreProfileRequest;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

final class ProfileRequestNotification extends Notification
{
    use Queueable;

    public function __construct(
        private readonly StoreProfileRequest $profileRequest
    ) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        $store = $this->profileRequest->store;

        return [
            'type' => 'profile_request_created',
            'profile_request_id' => $this->profileRequest->id,
            'store_id' => $store?->id,
            'store_name' => $store?->trade_name ?? $store?->store_name ?? 'Tienda desconocida',
            'seller_name' => $store?->owner?->name ?? 'Vendedor',
            'seller_email' => $store?->owner?->email,
            'fields_requested' => array_keys($this->profileRequest->data ?? []),
        ];
    }
}
