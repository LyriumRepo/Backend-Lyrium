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
use Illuminate\Support\Collection;

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

        if ($settings?->wantsPush() ?? true) {
            $channels[] = PushChannel::class;
        }

        if ($settings?->wantsEmailOrder() ?? true) {
            $channels[] = 'mail';
        }

        return $channels;
    }

    /**
     * Ítems (productos + servicios) de esta tienda dentro de la orden.
     */
    private function storeItems(): Collection
    {
        $products = $this->order->items->where('store_id', $this->store->id)
            ->map(fn ($item) => [
                'name' => $item->product_name,
                'quantity' => $item->quantity,
                'line_total' => (float) $item->line_total,
            ]);

        $services = $this->order->serviceItems->where('store_id', $this->store->id)
            ->map(fn ($item) => [
                'name' => $item->service_name,
                'quantity' => $item->quantity,
                'line_total' => (float) $item->line_total,
            ]);

        return $products->concat($services)->values();
    }

    private function orderTypeLabel(bool $hasProducts, bool $hasServices): string
    {
        return match (true) {
            $hasProducts && $hasServices => 'producto(s) y servicio(s)',
            $hasServices => 'servicio(s)',
            default => 'producto(s)',
        };
    }

    public function toMail(object $notifiable): MailMessage
    {
        $items = $this->storeItems();
        $hasProducts = $this->order->items->where('store_id', $this->store->id)->isNotEmpty();
        $hasServices = $this->order->serviceItems->where('store_id', $this->store->id)->isNotEmpty();

        return (new MailMessage)
            ->subject('🆕 Nuevo pedido recibido - Lyrium BioMarketplace')
            ->view('emails.notifications.new-order', [
                'name' => $notifiable->name,
                'storeName' => $this->store->trade_name,
                'orderNumber' => $this->order->order_number,
                'customerName' => $this->order->shipping_name ?: $this->order->user->name,
                'itemsCount' => $items->count(),
                'total' => (float) $items->sum('line_total'),
                'items' => $items->toArray(),
                'orderTypeLabel' => $this->orderTypeLabel($hasProducts, $hasServices),
                'shippingAddress' => $this->order->shipping_address,
                'actionUrl' => config('app.frontend_url') . '/seller/orders/' . $this->order->id,
            ]);
    }

    public function toPush(object $notifiable): array
    {
        $items = $this->storeItems();
        $hasProducts = $this->order->items->where('store_id', $this->store->id)->isNotEmpty();
        $hasServices = $this->order->serviceItems->where('store_id', $this->store->id)->isNotEmpty();
        $label = $this->orderTypeLabel($hasProducts, $hasServices);

        return [
            'title' => '¡Nuevo pedido recibido!',
            'body' => "{$items->count()} {$label} en {$this->store->trade_name} por S/ " . number_format((float) $items->sum('line_total'), 2),
            'data' => [
                'type' => 'new_order',
                'order_id' => $this->order->id,
                'store_id' => $this->store->id,
            ],
        ];
    }

    public function toArray(object $notifiable): array
    {
        $items = $this->storeItems();
        $hasProducts = $this->order->items->where('store_id', $this->store->id)->isNotEmpty();
        $hasServices = $this->order->serviceItems->where('store_id', $this->store->id)->isNotEmpty();
        $label = $this->orderTypeLabel($hasProducts, $hasServices);
        $total = (float) $items->sum('line_total');
        $count = $items->count();

        return [
            'type' => 'new_order',
            'order_id' => $this->order->id,
            'order_number' => $this->order->order_number,
            'store_id' => $this->store->id,
            'store_name' => $this->store->trade_name,
            'total' => $total,
            'items_count' => $count,
            'customer_name' => $this->order->shipping_name ?: $this->order->user->name,
            'subject' => "Nuevo pedido #{$this->order->order_number} en {$this->store->trade_name} — S/ {$total} ({$count} {$label})",
        ];
    }
}
