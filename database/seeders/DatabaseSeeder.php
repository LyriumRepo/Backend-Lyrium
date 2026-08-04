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
            CommissionTierSeeder::class,
            GlossaryEntrySeeder::class,
            SecurityAdminSeeder::class,
            SpecialistSeeder::class,
            LogisticsSeeder::class,
            BlogDemoSeeder::class,
            ForumCategorySeeder::class,
            ForumDemoSeeder::class,
            BlogCommentDemoSeeder::class,
            DemoStoresSeeder::class,
            DemoStoresWithImagesSeeder::class,
            ContractDemoSeeder::class,
            StoreMediaSeeder::class,
        ]);
    }
}
