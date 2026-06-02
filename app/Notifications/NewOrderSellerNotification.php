<?php

declare(strict_types=1);

namespace App\Notifications;

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
        return ['mail', 'database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $storeItems = $this->order->items->where('store_id', $this->store->id);
        $totalStore = $storeItems->sum('line_total');

        $mail = (new MailMessage)
            ->subject('Nuevo pedido recibido - Lyrium BioMarketplace')
            ->greeting('¡Hola, ' . $notifiable->name . '!')
            ->line('Has recibido un nuevo pedido en tu tienda "' . $this->store->trade_name . '".')
            ->line('**N° Pedido:** ' . $this->order->order_number)
            ->line('**Cliente:** ' . ($this->order->shipping_name ?: $this->order->user->name));

        $lines = [];
        foreach ($storeItems as $item) {
            $lines[] = $item->quantity . 'x ' . $item->product_name . ' — S/ ' . number_format($item->line_total, 2);
        }

        $mail->line('**Productos:**');
        foreach ($lines as $line) {
            $mail->line('• ' . $line);
        }

        $mail->line('**Total del pedido (tu tienda):** S/ ' . number_format((float) $totalStore, 2));

        if ($this->order->shipping_address) {
            $mail->line('**Dirección de envío:** ' . $this->order->shipping_address);
        }

        return $mail
            ->action('Ver pedido', config('app.frontend_url') . '/seller/orders/' . $this->order->id)
            ->line('Revisa y confirma el pedido lo antes posible.')
            ->salutation('Equipo Lyrium');
    }

    public function toArray(object $notifiable): array
    {
        $storeItems = $this->order->items->where('store_id', $this->store->id);

        return [
            'type' => 'new_order',
            'order_id' => $this->order->id,
            'order_number' => $this->order->order_number,
            'store_id' => $this->store->id,
            'store_name' => $this->store->trade_name,
            'total' => (float) $storeItems->sum('line_total'),
            'items_count' => $storeItems->count(),
            'customer_name' => $this->order->shipping_name ?: $this->order->user->name,
        ];
    }
}
