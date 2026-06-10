<?php

declare(strict_types=1);

namespace App\Listeners;

use App\Events\NewOrderReceived;
use App\Models\Store;
use App\Notifications\NewOrderSellerNotification;
use Illuminate\Support\Facades\Log;

final class SendNewOrderToSellerListener
{
    public function handle(NewOrderReceived $event): void
    {
        $store = Store::with('owner')->find($event->storeId);

        if (! $store) {
            Log::warning('SendNewOrderToSellerListener: tienda no encontrada', [
                'store_id' => $event->storeId,
                'order_id' => $event->order->id,
            ]);
            return;
        }

        $owner = $store->owner;

        if (! $owner) {
            Log::warning('SendNewOrderToSellerListener: tienda sin propietario', [
                'store_id' => $store->id,
                'store_name' => $store->trade_name,
            ]);
            return;
        }

        $order = $event->order->loadMissing(['items', 'user']);

        $owner->notify(new NewOrderSellerNotification(
            order: $order,
            store: $store,
        ));

        Log::info('SendNewOrderToSellerListener: notificación enviada', [
            'store_id' => $store->id,
            'owner_id' => $owner->id,
            'order_id' => $order->id,
            'email' => $owner->email,
        ]);
    }
}
