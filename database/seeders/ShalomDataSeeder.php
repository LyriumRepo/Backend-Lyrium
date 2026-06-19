<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * ShalomDataSeeder
 *
 * Siembra shalom_data desde database/data/shalom_data.json.
 *
 * Este JSON ya está procesado y combina:
 *   - shalom-ids-reales.json       (terminales con ter_id)
 *   - distritos_con_reparto_shalom.json (datos de reparto a domicilio)
 *
 * Ejecutar:
 *   php artisan db:seed --class=ShalomDataSeeder
 */
class ShalomDataSeeder extends Seeder
{
    public function run(): void
    {
        $this->command->info('🚚 Sembrando shalom_data...');

        $jsonPath = database_path('data/shalom_data.json');

        if (!file_exists($jsonPath)) {
            $this->command->error("   ❌ No se encontró: {$jsonPath}");
            $this->command->warn('   Asegúrate de que database/data/shalom_data.json existe.');
            return;
        }

        $raw = file_get_contents($jsonPath);
        $data = json_decode($raw, true);

        if (!$data || !is_array($data)) {
            $this->command->error('   ❌ shalom_data.json inválido o vacío.');
            return;
        }

        // Limpiar tabla antes de re-sembrar (truncate es seguro aquí)
        Schema::disableForeignKeyConstraints();
        DB::table('shalom_data')->truncate();
        Schema::enableForeignKeyConstraints();

        $chunks = array_chunk($data, 100);
        $total  = 0;

        foreach ($chunks as $chunk) {
            $rows = array_map(function ($row) {
                // tarifas_reparto puede venir como array o como string JSON
                if (isset($row['tarifas_reparto']) && is_array($row['tarifas_reparto'])) {
                    $row['tarifas_reparto'] = json_encode($row['tarifas_reparto']);
                }

                // Asegurarse de que solo están las columnas que existen en la tabla
                return array_intersect_key($row, array_flip([
                    'ter_id', 'nombre', 'departamento', 'provincia',
                    'zona', 'direccion', 'abreviatura', 'ubigeo',
                    'latitud', 'longitud', 'tiene_reparto',
                    'departamento_id', 'provincia_id', 'distrito_id',
                    'ubi_id', 'tarifas_reparto',
                ]));
            }, $chunk);

            DB::table('shalom_data')->insert($rows);
            $total += count($rows);
        }

        $this->command->info("   ✅ {$total} registros Shalom sembrados.");
    }
}
