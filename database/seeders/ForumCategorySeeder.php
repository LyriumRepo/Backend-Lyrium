<?php

namespace Database\Seeders;

use App\Models\ForumCategory;
use Illuminate\Database\Seeder;

class ForumCategorySeeder extends Seeder
{
    public function run(): void
    {
        $categories = [
            ['id' => 1, 'name' => 'General',          'slug' => 'general',          'description' => 'Temas generales de la comunidad',             'sort_order' => 1],
            ['id' => 2, 'name' => 'Nutrición',         'slug' => 'nutricion',        'description' => 'Alimentación saludable y nutrición',           'sort_order' => 2],
            ['id' => 3, 'name' => 'Microbiota',        'slug' => 'microbiota',       'description' => 'Cuidado de la microbiota intestinal',          'sort_order' => 3],
            ['id' => 4, 'name' => 'Fitness',           'slug' => 'fitness',          'description' => 'Ejercicio y vida activa',                       'sort_order' => 4],
            ['id' => 5, 'name' => 'Salud Mental',      'slug' => 'salud-mental',     'description' => 'Bienestar emocional y salud mental',           'sort_order' => 5],
        ];

        foreach ($categories as $category) {
            ForumCategory::firstOrCreate(
                ['id' => $category['id']],
                $category
            );
        }
    }
}
