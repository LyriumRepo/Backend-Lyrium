<?php

namespace Database\Seeders;

use App\Models\Plan;
use App\Models\PlanRequest;
use App\Models\Store;
use Illuminate\Database\Seeder;

class PlanRequestSeeder extends Seeder
{
    public function run(): void
    {
        $store = Store::where('ruc', '20123456781')->first();

        if (! $store) {
            $this->command->warn('Store not found. Run AdminUserSeeder first.');

            return;
        }

        $emprendePlan = Plan::where('slug', 'emprende')->first();

        if (! $emprendePlan) {
            $this->command->warn('Plan not found. Run PlanSeeder first.');

            return;
        }

        // Crear solicitud de plan trial/upgrade para el vendedor
        PlanRequest::updateOrCreate(
            [
                'store_id' => $store->id,
                'status' => 'pending',
            ],
            [
                'plan_id' => $emprendePlan->id,
                'current_plan_id' => null,
                'payment_method' => 'trial',
                'payment_status' => 'paid',
                'izipay_order_id' => null,
            ]
        );

        $this->command->info('Plan request created for BioTienda Demo');
    }
}
