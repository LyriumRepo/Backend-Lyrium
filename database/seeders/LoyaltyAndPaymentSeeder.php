<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\LoyaltyProgram;
use App\Models\LoyaltyReward;
use App\Models\LoyaltyTier;
use App\Models\PaymentSchedule;
use Illuminate\Database\Seeder;

final class LoyaltyAndPaymentSeeder extends Seeder
{
    public function run(): void
    {
        $this->seedPaymentSchedules();
        $this->seedLoyaltyProgram();
    }

    private function seedPaymentSchedules(): void
    {
        $days = ['monday', 'tuesday', 'wednesday'];

        foreach ($days as $day) {
            PaymentSchedule::updateOrCreate(
                ['day' => $day],
                [
                    'name' => ucfirst($day).' Payment',
                    'day' => $day,
                    'is_active' => true,
                    'cutoff_time' => '17:00:00',
                ]
            );
        }
    }

    private function seedLoyaltyProgram(): void
    {
        $program = LoyaltyProgram::updateOrCreate(
            ['id' => 1],
            [
                'name' => 'Programa Lyrium',
                'description' => 'Gana puntos con cada compra y canjea por recompensas exclusivas',
                'is_active' => true,
                'points_per_currency' => 10,
                'currency_per_point' => 0.10,
                'min_points_to_redeem' => 100,
            ]
        );

        $tiers = [
            ['name' => 'Bronze', 'min_points' => 0, 'bonus_rate' => 0, 'is_default' => true],
            ['name' => 'Silver', 'min_points' => 500, 'bonus_rate' => 10, 'is_default' => false],
            ['name' => 'Gold', 'min_points' => 1500, 'bonus_rate' => 25, 'is_default' => false],
            ['name' => 'Platinum', 'min_points' => 5000, 'bonus_rate' => 50, 'is_default' => false],
        ];

        foreach ($tiers as $tier) {
            LoyaltyTier::updateOrCreate(
                ['program_id' => $program->id, 'name' => $tier['name']],
                [
                    'program_id' => $program->id,
                    'name' => $tier['name'],
                    'min_points' => $tier['min_points'],
                    'bonus_rate' => $tier['bonus_rate'],
                    'is_default' => $tier['is_default'],
                    'benefits' => match ($tier['name']) {
                        'Bronze' => '1 punto por cada $1 gastado',
                        'Silver' => '1.1 puntos por cada $1 gastado, ofertas exclusivas',
                        'Gold' => '1.25 puntos por cada $1 gastado, shipping gratis',
                        'Platinum' => '1.5 puntos por cada $1 gastado, shipping gratis priority, acceso anticipado',
                        default => null,
                    },
                ]
            );
        }

        $rewards = [
            ['name' => 'Descuento $50', 'reward_type' => 'discount_fixed', 'value' => 50, 'points_required' => 500],
            ['name' => 'Descuento 10%', 'reward_type' => 'discount_percentage', 'value' => 10, 'points_required' => 300],
            ['name' => 'Descuento 20%', 'reward_type' => 'discount_percentage', 'value' => 20, 'points_required' => 600],
            ['name' => 'Envío Gratis', 'reward_type' => 'free_shipping', 'value' => 0, 'points_required' => 200],
            ['name' => 'Descuento $100', 'reward_type' => 'discount_fixed', 'value' => 100, 'points_required' => 1000],
        ];

        foreach ($rewards as $reward) {
            LoyaltyReward::updateOrCreate(
                ['program_id' => $program->id, 'name' => $reward['name']],
                [
                    'program_id' => $program->id,
                    'name' => $reward['name'],
                    'reward_type' => $reward['reward_type'],
                    'value' => $reward['value'],
                    'points_required' => $reward['points_required'],
                    'is_active' => true,
                ]
            );
        }
    }
}
