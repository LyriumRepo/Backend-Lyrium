<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Product;
use App\Models\Store;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Product>
 */
final class ProductFactory extends Factory
{
    protected $model = Product::class;

    public function definition(): array
    {
        $name = fake()->words(3, true);

        return [
            'store_id' => Store::factory(),
            'type' => 'physical',
            'name' => $name,
            'slug' => Str::slug($name).'-'.Str::random(5),
            'description' => fake()->paragraph(),
            'price' => fake()->randomFloat(2, 5, 500),
            'stock' => fake()->numberBetween(0, 200),
            'status' => 'draft',
        ];
    }

    public function physical(): static
    {
        return $this->state(fn () => [
            'type' => 'physical',
            'weight' => fake()->randomFloat(2, 0.1, 50),
            'dimensions' => '10x15x5',
        ]);
    }

    public function digital(): static
    {
        return $this->state(fn () => [
            'type' => 'digital',
            'download_url' => fake()->url(),
            'download_limit' => 5,
            'file_type' => 'pdf',
            'file_size' => 1024,
        ]);
    }

    public function service(): static
    {
        return $this->state(fn () => [
            'type' => 'service',
            'service_duration' => 60,
            'service_modality' => 'presencial',
            'service_location' => fake()->city(),
        ]);
    }

    public function approved(): static
    {
        return $this->state(fn () => ['status' => 'approved']);
    }

    public function pendingReview(): static
    {
        return $this->state(fn () => ['status' => 'pending_review']);
    }

    public function rejected(): static
    {
        return $this->state(fn () => ['status' => 'rejected']);
    }
}
