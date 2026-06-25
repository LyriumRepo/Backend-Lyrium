<?php

namespace Database\Seeders;

use App\Models\Product;
use App\Models\Service;
use App\Models\Store;
use Illuminate\Database\Seeder;

class ProductImagesSeeder extends Seeder
{
    private string $baseDir = 'C:\\Users\\roaca\\PROYECTO_LYRIUM\\PRODUCTOS Y SERVICIOS-20260618T042351Z-3-001\\PRODUCTOS Y SERVICIOS';

    public function run(): void
    {
        config(['media-library.max_file_size' => 30 * 1024 * 1024]);

        // ── 1. VIDA NATURAL — products ──────────────────────────────
        $this->attachImages('vida-natural', 'products', [
            'Aceite MCT Coco 500 ml Keto'                          => '1 VIDA NATURAL/1 vida natural aceite MCT.png',
            'Bebida de Almendra Vainilla sin azúcar Pack 3 Unid. Caja 946ml' => '1 VIDA NATURAL/1 vida natural bebida de almendras.png',
            'Moringa Cápsulas x 400mg Pack 2 Unid. 100 caps'      => '1 VIDA NATURAL/1 moringa capsulas.png',
            'Colágeno Marino Hidrolizado'                           => '1 VIDA NATURAL/1 vida natural colageno marino.png',
        ]);

        // ── 2. VIÑA SOL — products ──────────────────────────────────
        $this->attachImages('vina-sol', 'products', [
            'Cápsulas de Spirulina y Noni'  => '2 VIÑA SOL/2 CAPSULAS DE ESPIRULINA.jpeg',
            'Eritritol 500 g'               => '2 VIÑA SOL/2 ERITITOL.jpeg',
            'Maca Negra + Creatina 300 g'   => '2 VIÑA SOL/2 MACA NEGRA.jpeg',
            'Pecanas Peladas Enteras 1 Kg'  => '2 VIÑA SOL/2 PECANAS PELADAS.jpeg',
        ]);

        // ── 3. MAS NATURAL — products ───────────────────────────────
        $this->attachImages('mas-natural', 'products', [
            'Aloe Vera Tópico 200 ml'   => '3 MAS NATURAL/3 ALOE VERUM.png',
            'L-Carnitine x 60 Tabletas' => '3 MAS NATURAL/3 L-CARNITINE.jpeg',
            'Colágeno Hidrolizado en Gel' => '3 MAS NATURAL/3 CITRATO DE MAGNESIO.png',
            'Organic Plant Protein 1 Kg'  => '3 MAS NATURAL/3 Melatonina .jpeg',
        ]);

        // ── 4. AMO SPA — services ───────────────────────────────────
        $this->attachImages('amo-spa', 'services', [
            'Botox Capilar Orgánico'  => '4 AMO SPA/4 BOTOX ORGANICO.png',
            'Exfoliación Corporal'    => '4 AMO SPA/4 EXFOLIACION CORPORAL.png',
            'Limpieza Hydrafacial'    => '4 AMO SPA/4 LIMPIEZA HIDRAFACIAL.png',
            'Limpieza Hyalu-C'        => '4 AMO SPA/4 Limpieza Hyalu- C.png',
            'Manicure'                => '4 AMO SPA/4 MANICURA.png',
            'Masaje Corporal'         => '4 AMO SPA/4 MASAJE CORPORAL.png',
            'Pedicura'                => '4 AMO SPA/4 PEDICURA.png',
        ]);

        // ── 5. FIT BODY — services ──────────────────────────────────
        $this->attachImages('fit-body', 'services', [
            'Boxeo'                 => '5 FIT BODY/5 BOXEO.jpeg',
            'Personal Trainer'      => '5 FIT BODY/5 ENTRENAMIENTO PERSONALIZADO.jpeg',
            'Master Class Funcional' => '5 FIT BODY/5 FUNCIONAL.jpeg',
            'Mensualidad Gimnasio'  => '5 FIT BODY/5 Mensualidad.jpeg',
            'Clases de Zumba'       => '5 FIT BODY/5 ZUMBA.png',
        ]);

        // ── 6. CENTRO MEDICO DIGITAL — services ─────────────────────
        $this->attachImages('centro-medico-digital', 'services', [
            'Examen de Hemoglobina'  => '6 CENTRO MEDICO DIGITAL/6 EXAMEN HEMOGLOBINA.jpeg',
            'Ecografía Obstétrica'   => '6 CENTRO MEDICO DIGITAL/6 Ecografía obstetrica.jpeg',
            'Tomografía Multicorte'  => '6 CENTRO MEDICO DIGITAL/6 TOMOGRAFIA MULTICORTE.png',
            'Examen Cardiovascular'  => '6 CENTRO MEDICO DIGITAL/6 examen cardiovascular.jpeg',
        ]);

        // ── 7. PLAZA MEDIC — products ───────────────────────────────
        $this->attachImages('plaza-medic', 'products', [
            'Glucómetro Accu-Chek Instant'         => '7 PLAZA MEDIC/7 Glucómetro Accu-Chek Instant + 10 Tiras + 10 Lanc.png',
            'Muletas Ortopédicas de Aluminio'      => '7 PLAZA MEDIC/7 Muletas Ortopedicas de Aluminio Importadas Regulable.png',
            'Silla de Ruedas'                      => '7 PLAZA MEDIC/7 Silla de Ruedas Traumatológica.png',
            'Tensiómetro de Brazo BP1307'          => '7 PLAZA MEDIC/7 Tensiometro De Brazo Bp 1307 Gama Presión Arterial Lcd 3.2 Color Blanco.png',
        ]);

        // ── 8. FISIOCENTER — services ───────────────────────────────
        $this->attachImages('fisiocenter', 'services', [
            'Crioterapia'         => '8 FISIOCENTER/8 Crioterapia.png',
            'Electroterapia'      => '8 FISIOCENTER/8 Electroterapia.jpeg',
            'Terapia Neurológica' => '8 FISIOCENTER/8 Terapia Neurológica.jpeg',
            'Termoterapia'        => '8 FISIOCENTER/8 Termoterapia.png',
        ]);

        // ── 9. RYDENT — services ────────────────────────────────────
        $this->attachImages('rydent', 'services', [
            'Blanqueamiento Dental'         => '9 RYDENT/9 BLANQUEAMIENTO DENTAL.png',
            'Curación Temporal'             => '9 RYDENT/9 CURACION TEMPORAL.png',
            'Ortodoncia con Brackets'       => '9 RYDENT/9 Ortodoncia con Brackets.png',
            'Profilaxis / Destartraje / Flúor' => '9 RYDENT/9 Profilaxis Destartaje Fluor.png',
        ]);

        // ── 10. SOTOMAYOR — services ────────────────────────────────
        $this->attachImages('sotomayor', 'services', [
            'Angiografía Retina Unilateral'       => '10 SOTOMAYOR/10 ANGIOGRAFIA RETINA UNILATERAL.jpeg',
            'Gonioscopía'                          => '10 SOTOMAYOR/10 GONIOSCOPÌA.png',
            'Lavado y Sondeo del Tracto Lagrimal' => '10 SOTOMAYOR/10 LAVADO Y SONDEO DEL TRACTO LAGRIMAL.png',
        ]);

        // ── 11. SAN JUAN DE DIOS — services ────────────────────────
        $this->attachImages('san-juan-de-dios-psiquiatria', 'services', [
            'Diagnóstico Unipolar'                    => '11 SAN JUAN DE DIOS/11 Diagnostico unipolar.jpeg',
            'Programas de Rehabilitación Integral'    => '11 SAN JUAN DE DIOS/11 Programas de Rehabilitación Integral.png',
            'Terapia Psicológica'                     => '11 SAN JUAN DE DIOS/11 Terapia Psicologica.jpeg',
        ]);

        $this->command->info('✓ ProductImagesSeeder completado.');
    }

    private function attachImages(string $storeSlug, string $type, array $map): void
    {
        $store = Store::where('slug', $storeSlug)->first();
        if (! $store) {
            $this->command->warn("[{$storeSlug}] Tienda no encontrada.");
            return;
        }

        $this->command->info("Tienda: {$storeSlug}");

        foreach ($map as $name => $relPath) {
            $srcPath = $this->baseDir . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $relPath);

            if (! file_exists($srcPath)) {
                $this->command->warn("  [img no encontrada] " . basename($srcPath));
                continue;
            }

            $model = $type === 'products'
                ? Product::where('store_id', $store->id)->where('name', $name)->first()
                : Service::where('store_id', $store->id)->where('name', $name)->first();

            if (! $model) {
                $this->command->warn("  [no encontrado] {$name}");
                continue;
            }

            if ($model->getMedia('images')->isNotEmpty()) {
                $this->command->line("  [skip] {$name} ya tiene imagen");
                continue;
            }

            $tmpPath = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'lyrium_' . uniqid() . '_' . basename($srcPath);
            copy($srcPath, $tmpPath);

            try {
                $model->addMedia($tmpPath)
                    ->usingFileName(basename($srcPath))
                    ->toMediaCollection('images');
                $this->command->info("  ✓ {$name}");
            } catch (\Exception $e) {
                $this->command->error("  [error] {$name}: " . $e->getMessage());
                @unlink($tmpPath);
            }
        }
    }
}
