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

final class NewOrderSellerNotification extends Notification implements ShouldQueue
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
        $storeItems = $this->order->items->where('store_id', $this->store->id);

        $items = $storeItems->map(fn($item) => [
            'name' => $item->product_name,
            'quantity' => $item->quantity,
            'line_total' => (float) $item->line_total,
        ])->toArray();

        return (new MailMessage)
            ->subject('🆕 Nuevo pedido recibido - Lyrium BioMarketplace')
            ->view('emails.notifications.new-order', [
                'name' => $notifiable->name,
                'storeName' => $this->store->trade_name,
                'orderNumber' => $this->order->order_number,
                'customerName' => $this->order->shipping_name ?: $this->order->user->name,
                'itemsCount' => $storeItems->count(),
                'total' => (float) $storeItems->sum('line_total'),
                'items' => $items,
                'shippingAddress' => $this->order->shipping_address,
                'actionUrl' => config('app.frontend_url') . '/seller/orders/' . $this->order->id,
                'showTagline' => true,
            ])
            ->withSymfonyMessage(function ($message) {
                $iconPath = public_path('images/iconologo.png');
                $textPath = public_path('images/nombrelogo.png');
                if (file_exists($iconPath)) {
                    $message->embedFromPath($iconPath, 'logo-icon');
                }
                if (file_exists($textPath)) {
                    $message->embedFromPath($textPath, 'logo-text');
                }
            });
    }

    public function toPush(object $notifiable): array
    {
        $storeItems = $this->order->items->where('store_id', $this->store->id);
        $count = $storeItems->count();

        return [
            'title' => '¡Nuevo pedido recibido!',
            'body' => "{$count} producto(s) en {$this->store->trade_name} por S/ " . number_format((float) $storeItems->sum('line_total'), 2),
            'data' => [
                'type' => 'new_order',
                'order_id' => $this->order->id,
                'store_id' => $this->store->id,
            ],
        ];
    }

    public function toArray(object $notifiable): array
    {
        $storeItems = $this->order->items->where('store_id', $this->store->id);
        $total = (float) $storeItems->sum('line_total');
        $count = $storeItems->count();

        return [
            'type' => 'new_order',
            'order_id' => $this->order->id,
            'order_number' => $this->order->order_number,
            'store_id' => $this->store->id,
            'store_name' => $this->store->trade_name,
            'total' => $total,
            'items_count' => $count,
            'customer_name' => $this->order->shipping_name ?: $this->order->user->name,
            'subject' => "Nuevo pedido #{$this->order->order_number} en {$this->store->trade_name} — S/ {$total} ({$count} producto(s))",
        ];
    }
}
