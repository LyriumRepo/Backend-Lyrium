<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            RoleSeeder::class,
            PlanSeeder::class,
            AdminUserSeeder::class,
            PlanRequestSeeder::class,
            CategorySeeder::class,
            HomeSeeder::class,
            LoyaltyAndPaymentSeeder::class,
            ShippingSeeder::class,
        ]);
    }
}
