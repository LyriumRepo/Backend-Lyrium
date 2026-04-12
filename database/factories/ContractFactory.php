<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Contract;
use App\Models\Store;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Contract>
 */
final class ContractFactory extends Factory
{
    protected $model = Contract::class;

    public function definition(): array
    {
        return [
            'contract_number' => 'CTR-'.now()->year.'-'.fake()->unique()->numerify('###'),
            'store_id' => Store::factory(),
            'company' => fake()->company(),
            'ruc' => fake()->numerify('###########'),
            'representative' => fake()->name(),
            'type' => 'Servicio de Distribución',
            'modality' => 'VIRTUAL',
            'status' => 'PENDING',
            'start_date' => now(),
            'end_date' => now()->addYear(),
        ];
    }

    public function active(): static
    {
        return $this->state(fn () => ['status' => 'ACTIVE']);
    }

    public function expired(): static
    {
        return $this->state(fn () => [
            'status' => 'EXPIRED',
            'end_date' => now()->subDay(),
        ]);
    }

    public function expiringsSoon(): static
    {
        return $this->state(fn () => [
            'status' => 'ACTIVE',
            'end_date' => now()->addDays(10),
        ]);
    }
}
