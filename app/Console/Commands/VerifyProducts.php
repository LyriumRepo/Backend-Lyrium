<?php

namespace App\Console\Commands;

use App\Models\Product;
use Illuminate\Console\Command;

class VerifyProducts extends Command
{
    protected $signature = 'app:verify-products';

    protected $description = 'Verify products in database';

    public function handle(): int
    {
        $total = Product::count();
        $approved = Product::where('status', 'approved')->count();
        $this->info("Total products: $total");
        $this->info("Approved products: $approved");

        $samples = Product::with('categories')->limit(3)->get();
        foreach ($samples as $p) {
            $cats = $p->categories->pluck('name')->implode(', ');
            $this->line("- {$p->name} ({$p->status}) | {$p->price} | cats: $cats");
        }

        return self::SUCCESS;
    }
}
