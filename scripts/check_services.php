<?php

echo "=== SERVICE 48 DETAIL ===\n";
$s = App\Models\Service::find(48);
if ($s) {
    echo "  ID:{$s->id} Store:{$s->store_id} Name:{$s->name} Price:{$s->price} Duration:{$s->duration_minutes}\n";
    echo "  Status:{$s->status} CatID:{$s->category_id} is_home_service:{$s->is_home_service}\n";
    echo "  booking_advance_hours:{$s->booking_advance_hours} max_capacity:{$s->max_capacity}\n";
} else {
    echo "  NOT FOUND\n";
}

echo "\n=== STORE 1 DETAIL ===\n";
$st = App\Models\Store::find(1);
if ($st) {
    echo "  ID:{$st->id} Name:{$st->trade_name} SellerType:{$st->seller_type} Status:{$st->status}\n";
}

echo "\n=== ALL SERVICES FOR STORE 1 ===\n";
App\Models\Service::where('store_id', 1)->get()->each(function($s) {
    echo "  ID:{$s->id} {$s->name} S/{$s->price} dur={$s->duration_minutes} status={$s->status} cat={$s->category_id}\n";
});

echo "\n=== CATEGORIES IDs ===\n";
App\Models\Category::whereIn('id', [197, 198, 206, 207, 208, 484])->get(['id','name','type'])->each(function($c) {
    echo "  ID:{$c->id} {$c->name} type={$c->type}\n";
});

echo "\n=== PRODUCTS for store 1 ===\n";
App\Models\Product::where('store_id', 1)->get(['id','name','type','price','status'])->each(function($p) {
    echo "  ID:{$p->id} {$p->name} S/{$p->price} type={$p->type} status={$p->status}\n";
});
