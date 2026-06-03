<?php

use App\Models\Order;
use App\Models\ServiceBooking;
use App\Models\Service;
use App\Models\Specialist;
use App\Models\ServiceSchedule;
use App\Models\OrderServiceItem;
use App\Models\OrderItem;

echo "=== VERIFICACIÓN DE DATOS CREADOS ===\n\n";

echo "--- SERVICES ---\n";
Service::where('store_id', 1)->get()->each(function($s) {
    echo "  ID:{$s->id} {$s->name} S/{$s->price} active={$s->status}\n";
});

echo "\n--- SPECIALISTS ---\n";
Specialist::where('store_id', 1)->get()->each(function($s) {
    echo "  ID:{$s->id} {$s->nombres} {$s->apellidos} - {$s->especialidad}\n";
});

echo "\n--- SCHEDULES ---\n";
ServiceSchedule::whereIn('service_id', Service::where('store_id', 1)->pluck('id'))->get()->groupBy('service_id')->each(function($schedules, $svcId) {
    $svc = Service::find($svcId);
    echo "  Service ID:{$svcId} ({$svc->name}): {$schedules->count()} schedules\n";
});

echo "\n--- SERVICE BOOKINGS ---\n";
ServiceBooking::all()->each(function($b) {
    $svc = Service::find($b->service_id);
    $user = \App\Models\User::find($b->user_id);
    echo "  ID:{$b->id} {$svc->name} | User:{$user->name} | Date:{$b->appointment_date} | Status:{$b->status} | Pay:{$b->payment_method}/{$b->payment_status}\n";
});

echo "\n--- ORDERS (ALL) ---\n";
Order::with(['items', 'serviceItems'])->get()->each(function($o) {
    $user = \App\Models\User::find($o->user_id);
    echo "  ID:{$o->id} {$o->order_number} | User:{$user->name} | Type:{$o->order_type} | Status:{$o->status} | Total:S/{$o->total} | Pay:{$o->payment_status}\n";
    if ($o->items->count() > 0) {
        echo "    Product Items: {$o->items->count()} - ";
        echo $o->items->map(fn($i) => "{$i->product_name} x{$i->quantity}")->implode(', ') . "\n";
    }
    if ($o->serviceItems->count() > 0) {
        echo "    Service Items: {$o->serviceItems->count()} - ";
        echo $o->serviceItems->map(fn($si) => "{$si->service_name} (booking:{$si->service_booking_id})")->implode(', ') . "\n";
    }
});

echo "\n--- ORDER SERVICE ITEMS ---\n";
OrderServiceItem::all()->each(function($osi) {
    echo "  ID:{$osi->id} Order:{$osi->order_id} | {$osi->service_name} S/{$osi->line_total} | Status:{$osi->status} | BookingID:{$osi->service_booking_id}\n";
});

echo "\n=== VERIFICACIÓN COMPLETADA ===\n";
