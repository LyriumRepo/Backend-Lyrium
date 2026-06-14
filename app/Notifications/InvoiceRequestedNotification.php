<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Channels\PushChannel;
use App\Models\Order;
use App\Models\Store;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Collection;

final class InvoiceRequestedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        private readonly Order $order,
        private readonly Store $store,
        private readonly Collection $storeItems,
    ) {}

    public function via(object $notifiable): array
    {
        $channels = ['database'];

        $settings = $notifiable->notificationSetting;
        if ($settings?->wantsPush() ?? true) {
            $channels[] = PushChannel::class;
        }

        // No mail channel per requirements

        return $channels;
    }

    public function toPush(object $notifiable): array
    {
        $customerName = $this->order->shipping_name ?: $this->order->user?->name ?? 'Un cliente';

        return [
            'title' => '📄 Solicitud de comprobante',
            'body' => "{$customerName} solicitó un comprobante por el pedido #{$this->order->order_number}",
            'data' => [
                'type' => 'invoice_requested',
                'order_id' => (string) $this->order->id,
                'order_number' => $this->order->order_number,
                'store_id' => (string) $this->store->id,
            ],
        ];
    }

    public function toArray(object $notifiable): array
    {
        $customerName = $this->order->shipping_name ?: $this->order->user?->name ?? 'Un cliente';
        $products = $this->storeItems->map(fn ($item) => $item->product_name)->join(', ');

        return [
            'type' => 'invoice_requested',
            'order_id' => $this->order->id,
            'order_number' => $this->order->order_number,
            'store_id' => $this->store->id,
            'store_name' => $this->store->trade_name,
            'customer_name' => $customerName,
            'products' => $products,
            'purchase_date' => $this->order->created_at->setTimezone('America/Lima')->format('d/m/Y'),
            'purchase_time' => $this->order->created_at->setTimezone('America/Lima')->format('H:i'),
            'subject' => "Solicitud de comprobante — Pedido #{$this->order->order_number} de {$customerName}",
        ];
    }
}
