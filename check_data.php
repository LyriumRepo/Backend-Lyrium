<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Store;
use App\Models\User;
use App\Models\Category;
use App\Models\Service;
use App\Models\Specialist;
use App\Models\Product;
use App\Models\Order;
use App\Models\Contract;

echo "=== STORES ===\n";
Store::all(['id','trade_name','slug','status','seller_type'])->each(function($s) {
    echo "  ID:{$s->id} {$s->trade_name} ({$s->slug}) status={$s->status} type={$s->seller_type}\n";
});

echo "\n=== USERS ===\n";
User::with('roles')->get()->each(function($u) {
    echo "  ID:{$u->id} {$u->name} ({$u->email}) roles=" . $u->roles->pluck('name')->implode(',') . "\n";
});

echo "\n=== CATEGORIES (type=service) ===\n";
Category::where('type','service')->get(['id','name','slug'])->each(function($c) {
    echo "  ID:{$c->id} {$c->name} ({$c->slug})\n";
});

echo "\n=== SERVICES TABLE ===\n";
echo "  Count: " . Service::count() . "\n";
Service::all(['id','store_id','name','price','status'])->each(function($s) {
    echo "  ID:{$s->id} Store:{$s->store_id} {$s->name} S/ {$s->price} status={$s->status}\n";
});

echo "\n=== SPECIALISTS ===\n";
echo "  Count: " . Specialist::count() . "\n";

echo "\n=== PRODUCTS (type=service) ===\n";
Product::where('type','service')->get(['id','store_id','name','price','status'])->each(function($p) {
    echo "  ID:{$p->id} Store:{$p->store_id} {$p->name} S/ {$p->price} status={$p->status}\n";
});

echo "\n=== ORDERS ===\n";
echo "  Count: " . Order::count() . "\n";
Order::all(['id','order_number','user_id','order_type','status','total','payment_status'])->each(function($o) {
    echo "  ID:{$o->id} {$o->order_number} user={$o->user_id} type={$o->order_type} status={$o->status} total=S/{$o->total} pay={$o->payment_status}\n";
});

echo "\n=== CONTRACTS ===\n";
Contract::all(['id','store_id','status'])->each(function($c) {
    echo "  ID:{$c->id} Store:{$c->store_id} status={$c->status}\n";
});
