<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\Cart;
use App\Models\CartItem;
use Illuminate\Console\Command;

final class CleanStaleCarts extends Command
{
    protected $signature = 'carts:clean-stale {--days=7 : Días de antigüedad para considerar un carrito como obsoleto}';

    protected $description = 'Elimina carritos de invitado obsoletos y sus items';

    public function handle(): int
    {
        $days = (int) $this->option('days');
        $cutoff = now()->subDays($days);

        $staleGuestCarts = Cart::whereNull('user_id')
            ->where('created_at', '<', $cutoff)
            ->get();

        $count = 0;
        foreach ($staleGuestCarts as $cart) {
            CartItem::where('cart_id', $cart->id)->delete();
            $cart->delete();
            $count++;
        }

        $this->info("Se eliminaron {$count} carritos de invitado obsoletos.");

        return self::SUCCESS;
    }
}
