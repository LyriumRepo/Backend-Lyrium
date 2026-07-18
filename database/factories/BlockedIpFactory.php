<?php

namespace Database\Factories;

use App\Models\BlockedIp;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\BlockedIp>
 */
class BlockedIpFactory extends Factory
{
    protected $model = BlockedIp::class;

    public function definition(): array
    {
        return [
            'ip_address' => $this->faker->ipv4(),
            'reason' => $this->faker->sentence(),
            'status' => BlockedIp::STATUS_BLOCKED,
            'expires_at' => now()->addDay(),
            'created_at' => now(),
        ];
    }
}
