<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Supplier;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Supplier>
 */
final class SupplierFactory extends Factory
{
    protected $model = Supplier::class;

    public function definition(): array
    {
        $name = fake()->name();
        $types = ['Economista', 'Contador', 'Ingeniero'];

        return [
            'name' => $name,
            'slug' => Str::slug($name).'-'.Str::random(5),
            'ruc' => fake()->unique()->numerify('###########'),
            'type' => fake()->randomElement($types),
            'especialidad' => fake()->jobTitle(),
            'status' => 'Activo',
            'fecha_renovacion' => fake()->dateTimeBetween('now', '+1 year'),
            'proyectos' => null,
            'certificaciones' => null,
            'total_gastado' => 0,
            'total_recibos' => 0,
        ];
    }

    public function suspendido(): static
    {
        return $this->state(fn () => ['status' => 'Suspendido']);
    }

    public function finalizado(): static
    {
        return $this->state(fn () => ['status' => 'Finalizado']);
    }

    public function economista(): static
    {
        return $this->state(fn () => ['type' => 'Economista']);
    }

    public function contador(): static
    {
        return $this->state(fn () => ['type' => 'Contador']);
    }

    public function ingeniero(): static
    {
        return $this->state(fn () => ['type' => 'Ingeniero']);
    }
}
