<?php

namespace Database\Seeders;

use App\Models\CommissionTier;
use Illuminate\Database\Seeder;

class CommissionTierSeeder extends Seeder
{
    public function run(): void
    {
        $tiers = [
            ['name' => 'Tramo 1', 'min_amount' => 0, 'max_amount' => 400, 'rate' => 15.00, 'sort_order' => 1],
            ['name' => 'Tramo 2', 'min_amount' => 401, 'max_amount' => 800, 'rate' => 14.00, 'sort_order' => 2],
            ['name' => 'Tramo 3', 'min_amount' => 801, 'max_amount' => 1200, 'rate' => 13.00, 'sort_order' => 3],
            ['name' => 'Tramo 4', 'min_amount' => 1201, 'max_amount' => null, 'rate' => 12.00, 'sort_order' => 4],
        ];

        foreach ($tiers as $tier) {
            CommissionTier::create($tier);
        }
    }
}
