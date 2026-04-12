<?php

namespace Database\Seeders;

use App\Models\Category;
use Illuminate\Database\Seeder;

class ServiceCategorySeeder extends Seeder
{
    public function run(): void
    {
        Category::disableSearchSyncing();
        $services = [
            ['name' => 'Servicios de Salud', 'slug' => 'servicios-salud', 'sort_order' => 1, 'type' => 'service', 'image' => '/img/Inicio/1/1.png'],
            ['name' => 'Medicina Natural', 'slug' => 'medicina-natural', 'sort_order' => 2, 'type' => 'service', 'image' => '/img/Inicio/1/2.png'],
            ['name' => 'Belleza y Cuidado', 'slug' => 'belleza-cuidado', 'sort_order' => 3, 'type' => 'service', 'image' => '/img/Inicio/1/3.png'],
            ['name' => 'Terapias Alternativas', 'slug' => 'terapias-alternativas', 'sort_order' => 4, 'type' => 'service', 'image' => '/img/Inicio/1/4.png'],
            ['name' => 'Nutrición', 'slug' => 'nutricion', 'sort_order' => 5, 'type' => 'service', 'image' => '/img/Inicio/1/1.png'],
        ];

        foreach ($services as $svc) {
            Category::updateOrCreate(['slug' => $svc['slug']], $svc);
        }
    }
}
