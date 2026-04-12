<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Store;
use App\Models\Ticket;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

final class TicketFactory extends Factory
{
    protected $model = Ticket::class;

    public function definition(): array
    {
        return [
            'ticket_number' => 'TKT-'.now()->format('Y').'-'.fake()->unique()->numerify('###'),
            'user_id' => User::factory(),
            'store_id' => Store::factory(),
            'assigned_admin_id' => null,
            'subject' => fake()->sentence(5),
            'description' => fake()->paragraph(),
            'category' => fake()->randomElement(['tech', 'admin', 'info', 'payments', 'documentation']),
            'priority' => fake()->randomElement(['low', 'medium', 'high', 'critical']),
            'status' => 'open',
            'is_critical' => false,
            'is_escalated' => false,
        ];
    }

    public function critical(): static
    {
        return $this->state(fn () => [
            'priority' => 'critical',
            'is_critical' => true,
        ]);
    }

    public function closed(): static
    {
        return $this->state(fn () => [
            'status' => 'closed',
            'closed_at' => now(),
        ]);
    }
}
