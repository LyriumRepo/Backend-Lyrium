<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Channels\PushChannel;
use App\Models\Order;
use App\Models\Store;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

final class OrderDeliveredSellerNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        private readonly Order $order,
        private readonly Store $store,
    ) {}

    public function via(object $notifiable): array
    {
        $channels = ['database'];

        $settings = $notifiable->notificationSetting;
        if ($settings?->wantsEmailOrder() ?? true) {
            $channels[] = 'mail';
        }
        if ($settings?->wantsPush() ?? true) {
            $channels[] = PushChannel::class;
        }

        return $channels;
    }

    public function toMail(object $notifiable): MailMessage
    {
        $customerName = $this->order->shipping_name ?: ($this->order->user?->name ?? 'El cliente');
        $storeItems   = $this->order->items->where('store_id', $this->store->id);
        $total        = (float) $storeItems->sum('line_total');

        return (new MailMessage)
            ->subject('✅ Pedido #' . $this->order->order_number . ' entregado — Lyrium BioMarketplace')
            ->view('emails.notifications.order-delivered-seller', [
                'name'         => $notifiable->name,
                'storeName'    => $this->store->trade_name,
                'orderNumber'  => $this->order->order_number,
                'customerName' => $customerName,
                'total'        => $total,
                'items'        => $storeItems->map(fn($item) => [
                    'name'       => $item->product_name,
                    'quantity'   => $item->quantity,
                    'line_total' => (float) $item->line_total,
                ])->toArray(),
                'actionUrl'    => config('app.frontend_url') . '/seller/orders',
            ]);
    }

    public function toPush(object $notifiable): array
    {
        return [
            'title' => '✅ Pedido entregado',
            'body' => "El cliente confirmó la recepción del pedido #{$this->order->order_number} en {$this->store->trade_name}",
            'data' => [
                'type' => 'order_delivered_seller',
                'order_id' => (string) $this->order->id,
                'order_number' => $this->order->order_number,
                'store_id' => (string) $this->store->id,
            ],
        ];
    }

    public function toArray(object $notifiable): array
    {
        $customerName = $this->order->shipping_name ?: $this->order->user?->name ?? 'El cliente';

        return [
            'type'         => 'order_delivered_seller',
            'order_id'     => $this->order->id,
            'order_number' => $this->order->order_number,
            'store_id'     => $this->store->id,
            'store_name'   => $this->store->trade_name,
            'customer_name' => $customerName,
            'subject'      => "Pedido #{$this->order->order_number} entregado — confirmado por {$customerName}",
        ];
    }
}
