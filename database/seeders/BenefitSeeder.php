<?php

namespace Database\Seeders;

use App\Models\Benefit;
use Illuminate\Database\Seeder;

class BenefitSeeder extends Seeder
{
    public function run(): void
    {
        Benefit::truncate();

        $benefits = [
            ['titulo' => 'Todo Salud', 'descripcion' => 'Tiendas saludables y ecoamigables', 'icono' => 'heart', 'position' => 1],
            ['titulo' => 'Tiendas Selectas', 'descripcion' => 'Tiendas de calidad cuidadosamente seleccionadas', 'icono' => 'storefront', 'position' => 2],
            ['titulo' => 'Mejores Precios', 'descripcion' => 'Mejores ofertas, promociones y descuentos', 'icono' => 'tag', 'position' => 3],
            ['titulo' => 'Seguridad', 'descripcion' => 'Biomarketplace 100% seguro', 'icono' => 'shield-check', 'position' => 4],
            ['titulo' => 'Rapidez', 'descripcion' => 'Mayor rapidez en tus compras', 'icono' => 'lightning', 'position' => 5],
            ['titulo' => 'Más Tiempo', 'descripcion' => 'Ahorra tiempo en transportarte y en colas presenciales', 'icono' => 'clock', 'position' => 6],
            ['titulo' => 'Donde Quieras', 'descripcion' => 'Envíos a todo el Perú', 'icono' => 'globe', 'position' => 7],
        ];

        foreach ($benefits as $benefit) {
            Benefit::create(array_merge($benefit, ['is_active' => true]));
        }
    }
}
