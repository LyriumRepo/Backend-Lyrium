<?php

declare(strict_types=1);

namespace App\Listeners;

use App\Events\OrderStatusChanged;
use App\Models\Order;
use App\Notifications\OrderStatusTrackingNotification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Log;

final class SendOrderTrackingEmailListener implements ShouldQueue
{
    private const TRACKABLE_STATUSES = [
        Order::STATUS_CONFIRMED,
        Order::STATUS_PROCESSING,
        Order::STATUS_SHIPPED,
        Order::STATUS_ON_THE_WAY,
        Order::STATUS_DELIVERED,
        Order::STATUS_CANCELLED,
    ];

    public function handle(OrderStatusChanged $event): void
    {
        $order = $event->order;

        if (!in_array($order->status, self::TRACKABLE_STATUSES, true)) {
            return;
        }

        $user = $order->user;

        if (!$user) {
            return;
        }

        if (!$order->relationLoaded('items')) {
            $order->load('items');
        }
        if (!$order->relationLoaded('serviceItems')) {
            $order->load('serviceItems');
        }

        try {
            $user->notify(new OrderStatusTrackingNotification($order));
        } catch (\Throwable $e) {
            Log::error('SendOrderTrackingEmailListener: error al notificar', [
                'order_id' => $order->id,
                'user_id' => $user->id,
                'error' => $e->getMessage(),
            ]);
        }

        // Al llegar a "delivered", invitar al cliente a validar la recepción.
        // Solo pedidos con productos: los pedidos 100% servicio se validan
        // a nivel de reserva (BookingCompletedCustomerNotification) para no duplicar.
        if (
            $order->status === Order::STATUS_DELIVERED
            && $order->items->isNotEmpty()
            && $order->customer_validated_at === null
        ) {
            try {
                $user->notify(new \App\Notifications\OrderDeliveredCustomerNotification($order));
            } catch (\Throwable $e) {
                Log::error('SendOrderTrackingEmailListener: error al enviar invitación de validación', [
                    'order_id' => $order->id,
                    'user_id' => $user->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }
    }
}
