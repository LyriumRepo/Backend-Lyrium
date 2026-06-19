<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * LogisticsSeeder
 *
 * Siembra:
 *   ✅ box_types         — 7 tipos de caja (XXS → XL)
 *   ✅ courier_operators — cobertura geográfica de couriers (desde operators_data.json)
 *
 * Ejecutar:
 *   php artisan db:seed --class=LogisticsSeeder
 */
class LogisticsSeeder extends Seeder
{
    public function run(): void
    {
        $this->seedBoxTypes();
        $this->seedCourierOperators();
    }

    // ── Tipos de caja ─────────────────────────────────────────────────────────
    private function seedBoxTypes(): void
    {
        $this->command->info('📦 Sembrando box_types...');

        $boxTypes = [
            ['nombre' => 'XXS', 'largo' => 15, 'ancho' => 10, 'alto' => 10, 'peso_max_kg' => 0.50,  'orden' => 1, 'activo' => true],
            ['nombre' => 'XS',  'largo' => 25, 'ancho' => 20, 'alto' => 12, 'peso_max_kg' => 1.00,  'orden' => 2, 'activo' => true],
            ['nombre' => 'S',   'largo' => 30, 'ancho' => 20, 'alto' => 12, 'peso_max_kg' => 2.00,  'orden' => 3, 'activo' => true],
            ['nombre' => 'M',   'largo' => 40, 'ancho' => 30, 'alto' => 20, 'peso_max_kg' => 5.00,  'orden' => 4, 'activo' => true],
            ['nombre' => 'ML',  'largo' => 50, 'ancho' => 40, 'alto' => 25, 'peso_max_kg' => 10.00, 'orden' => 5, 'activo' => true],
            ['nombre' => 'L',   'largo' => 60, 'ancho' => 40, 'alto' => 30, 'peso_max_kg' => 10.00, 'orden' => 6, 'activo' => true],
            ['nombre' => 'XL',  'largo' => 80, 'ancho' => 60, 'alto' => 40, 'peso_max_kg' => 25.00, 'orden' => 7, 'activo' => true],
        ];

        foreach ($boxTypes as $bt) {
            DB::table('box_types')->upsert(
                [$bt],
                ['nombre'],
                ['largo', 'ancho', 'alto', 'peso_max_kg', 'orden', 'activo']
            );
        }

        $this->command->info('   ✅ ' . count($boxTypes) . ' tipos de caja sembrados.');
    }

    // ── Operadores de courier por distrito ────────────────────────────────────
    private function seedCourierOperators(): void
    {
        $this->command->info('🗺️  Sembrando courier_operators...');

        $jsonPath = database_path('data/operators_data.json');

        if (!file_exists($jsonPath)) {
            $this->command->error("   ❌ No se encontró: {$jsonPath}");
            $this->command->warn('   Asegúrate de que database/data/operators_data.json existe.');
            return;
        }

        $data = json_decode(file_get_contents($jsonPath), true);

        if (!$data || !is_array($data)) {
            $this->command->error('   ❌ operators_data.json inválido o vacío.');
            return;
        }

        // Insertar en chunks de 500 para evitar límites de MySQL
        $chunks = array_chunk($data, 500);
        $total  = 0;

        foreach ($chunks as $chunk) {
            DB::table('courier_operators')->upsert(
                $chunk,
                ['departamento', 'provincia', 'distrito'],
                ['tiene_shalom', 'tiene_olva', 'tiene_sharf', 'tiene_urbano']
            );
            $total += count($chunk);
        }

        $this->command->info("   ✅ {$total} distritos sembrados.");
    }
}
