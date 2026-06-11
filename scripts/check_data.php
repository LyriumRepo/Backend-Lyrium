<?php

echo "=== STORES ===\n";
App\Models\Store::all(['id','trade_name','slug','status','seller_type'])->each(function($s) {
    echo "  ID:{$s->id} {$s->trade_name} ({$s->slug}) status={$s->status} type={$s->seller_type}\n";
});

echo "\n=== USERS ===\n";
App\Models\User::with('roles')->get()->each(function($u) {
    echo "  ID:{$u->id} {$u->name} ({$u->email}) roles=" . $u->roles->pluck('name')->implode(',') . "\n";
});

echo "\n=== CATEGORIES (type=service) ===\n";
App\Models\Category::where('type','service')->get(['id','name','slug'])->each(function($c) {
    echo "  ID:{$c->id} {$c->name} ({$c->slug})\n";
});

echo "\n=== CATEGORIES (type=product) ===\n";
App\Models\Category::where('type','product')->get(['id','name'])->each(function($c) {
    echo "  ID:{$c->id} {$c->name}\n";
});

echo "\n=== SERVICES TABLE ===\n";
echo "  Count: " . App\Models\Service::count() . "\n";

echo "\n=== SPECIALISTS ===\n";
echo "  Count: " . App\Models\Specialist::count() . "\n";

echo "\n=== SERVICE SCHEDULES ===\n";
echo "  Count: " . App\Models\ServiceSchedule::count() . "\n";

echo "\n=== SERVICE BOOKINGS ===\n";
echo "  Count: " . App\Models\ServiceBooking::count() . "\n";

echo "\n=== PRODUCTS (type=service) ===\n";
App\Models\Product::where('type','service')->get(['id','store_id','name','price','status'])->each(function($p) {
    echo "  ID:{$p->id} Store:{$p->store_id} {$p->name} S/ {$p->price} status={$p->status}\n";
});

echo "\n=== ORDERS ===\n";
echo "  Count: " . App\Models\Order::count() . "\n";

echo "\n=== CONTRACTS ===\n";
App\Models\Contract::all(['id','store_id','status'])->each(function($c) {
    echo "  ID:{$c->id} Store:{$c->store_id} status={$c->status}\n";
});

echo "\n=== PRODUCTS COUNT ===\n";
echo "  Count: " . App\Models\Product::count() . "\n";

echo "\n=== ORDER SERVICE ITEMS ===\n";
echo "  Count: " . App\Models\OrderServiceItem::count() . "\n";
