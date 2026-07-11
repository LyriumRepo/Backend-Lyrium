<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Service;
use App\Models\Specialist;
use Illuminate\Database\Seeder;

class SpecialistSeeder extends Seeder
{
    /**
     * Per-store specialist definitions.
     * Each entry: [nombres, apellidos, especialidad, sub_especialidad, document_number,
     *              anios_experiencia, numero_colegiatura, [service_ids or ALL]]
     * service_ids = indices of the store's services (1-based in the group), ALL = all store services.
     */
    private const STORE_SPECIALISTS = [
        // Store 5 — Amó Spa
        5 => [
            ['María Fernanda', 'Torres López', 'Esteticista Facial', 'Cuidado integral de la piel', '45829103', 8, 'CET-45829', [0, 1, 2]],
            ['Carmen Rosa', 'Gutiérrez Mendoza', 'Manicurista y Pedicurista', 'Uñas acrílicas y gel', '47281946', 6, 'CET-47281', [3, 4]],
            ['José Antonio', 'Silva Paredes', 'Masoterapeuta', 'Terapias corporales relajantes', '43182659', 10, 'CET-43182', [5, 6]],
        ],
        // Store 6 — Fit Body
        6 => [
            ['Roberto Carlos', 'Mendoza Salazar', 'Instructor de Fitness', 'Entrenamiento funcional grupal', '46283741', 7, 'CFF-46283', [0, 1, 2]],
            ['Patricia Elena', 'Vargas Huamán', 'Entrenadora Personal', 'Planes personalizados y coaching', '44829173', 9, 'CFF-44829', [3, 4]],
        ],
        // Store 7 — Centro Médico Digital
        7 => [
            ['Luis Alberto', 'Huamán Quispe', 'Cardiólogo', 'Electrocardiograma y evaluación cardiovascular', '41529387', 15, 'CMP-41529', [0]],
            ['Mónica Patricia', 'Rojas Castillo', 'Médico General', 'Laboratorio clínico y ecografía', '42837461', 12, 'CMP-42837', [1, 3]],
            ['Fernando Javier', 'Quispe Ríos', 'Radiólogo', 'Tomografía multicorte', '40392817', 18, 'CMP-40392', [2]],
        ],
        // Store 9 — Fisiocenter
        9 => [
            ['Ricardo Martín', 'Lozano Vega', 'Fisioterapeuta', 'Electroterapia, termoterapia y crioterapia', '45918273', 8, 'CTF-45918', [0, 1, 2]],
            ['Andrea Paola', 'Castillo Mendoza', 'Terapeuta Neurológica', 'Rehabilitación neurológica funcional', '46728391', 6, 'CTF-46728', [3]],
        ],
        // Store 10 — Rydent
        10 => [
            ['Miguel Ángel', 'Paredes Salinas', 'Ortodoncista', 'Ortodoncia con brackets y aligners', '41829374', 11, 'COP-41829', [0]],
            ['Katherine Lizeth', 'Salazar Torres', 'Odontóloga General', 'Blanqueamiento, profilaxis y curación', '43528197', 7, 'COP-43528', [1, 2, 3]],
        ],
        // Store 11 — Sotomayor
        11 => [
            ['Ricardo Arturo', 'Mendoza Ríos', 'Oftalmólogo', 'Diagnóstico y tratamiento ocular integral', '40293847', 20, 'CMP-40293', [0, 1, 2, 3]],
        ],
        // Store 12 — San Juan de Dios - Psiquiatría
        12 => [
            ['Paola Andrea', 'Navarro Silva', 'Psicóloga Clínica', 'Terapia psicológica integrativa', '45618392', 9, 'CNP-45618', [0]],
            ['Christian Paul', 'Vega Morales', 'Psiquiatra', 'Psiquiatría general y rehabilitación integral', '44182739', 13, 'CMP-44182', [1, 2, 3]],
        ],
    ];

    public function run(): void
    {
        $createdCount = 0;
        $assignCount = 0;
        $skippedStores = [];

        foreach (self::STORE_SPECIALISTS as $storeId => $specDefs) {
            $services = Service::where('store_id', $storeId)->get();

            if ($services->isEmpty()) {
                $skippedStores[] = "store {$storeId} (sin servicios)";
                continue;
            }

            // Idempotency: if ANY service in this store already has specialists, skip the store
            $alreadyAssigned = $services->some(fn (Service $s) => $s->specialists()->count() > 0);

            if ($alreadyAssigned) {
                $skippedStores[] = "store {$storeId} (ya tiene especialistas)";
                continue;
            }

            $serviceList = $services->values();

            foreach ($specDefs as [$nombres, $apellidos, $especialidad, $subEspecialidad, $docNumber, $exp, $colegiatura, $serviceIndices]) {
                $email = $this->makeEmail($nombres, $apellidos);

                $specialist = Specialist::create([
                    'store_id' => $storeId,
                    'nombres' => $nombres,
                    'apellidos' => $apellidos,
                    'document_type' => 'DNI',
                    'document_number' => $docNumber,
                    'email' => $email,
                    'especialidad' => $especialidad,
                    'sub_especialidad' => $subEspecialidad,
                    'anios_experiencia' => $exp,
                    'numero_colegiatura' => $colegiatura,
                    'availability' => Specialist::AVAILABILITY_DISPONIBLE,
                ]);

                $createdCount++;
                $assignCount += $this->assignServices($specialist, $serviceList, $serviceIndices);
            }

            $this->command->info("  Store {$storeId}: " . count($specDefs) . " especialistas creados");
        }

        $this->command->info("✓ {$createdCount} especialistas creados, {$assignCount} asignaciones realizadas");

        if ($skippedStores) {
            $this->command->warn('  Omitidos: ' . implode(', ', $skippedStores));
        }

        // Final verification
        $this->verifyCoverage();
    }

    private function makeEmail(string $nombres, string $apellidos): string
    {
        $parts = explode(' ', trim($nombres));
        $firstName = strtolower(trim($parts[0]));
        $lastName = strtolower(str_replace(' ', '-', trim($apellidos)));
        $base = "{$firstName}.{$lastName}@lyrium-demo.pe";
        $email = $base;
        $counter = 1;

        while (Specialist::where('email', $email)->exists()) {
            $counter++;
            $email = "{$firstName}.{$lastName}{$counter}@lyrium-demo.pe";
        }

        return $email;
    }

    private function assignServices(Specialist $specialist, iterable $serviceList, array $indices): int
    {
        $targets = [];
        foreach ($indices as $i) {
            if (isset($serviceList[$i])) {
                $targets[] = $serviceList[$i]->id;
            }
        }

        if (empty($targets)) {
            return 0;
        }

        $specialist->services()->syncWithoutDetaching($targets);

        return count($targets);
    }

    private function verifyCoverage(): void
    {
        $servicesWithoutSpecialists = Service::whereDoesntHave('specialists')
            ->where('status', 'active')
            ->count();

        $totalServices = Service::where('status', 'active')->count();
        $servicesWithSpecialists = Service::whereHas('specialists')
            ->where('status', 'active')
            ->count();

        $this->command->info('');
        $this->command->info('── Verificación de cobertura ──');
        $this->command->info("Servicios activos totales: {$totalServices}");
        $this->command->info("Con especialistas: {$servicesWithSpecialists}");
        $this->command->info("Sin especialistas: {$servicesWithoutSpecialists}");

        if ($servicesWithoutSpecialists > 0) {
            $this->command->warn('');
            $this->command->warn('Servicios sin especialistas:');
            Service::whereDoesntHave('specialists')
                ->where('status', 'active')
                ->each(function (Service $s) {
                    $this->command->warn("  - ID {$s->id}: {$s->name} (store {$s->store_id})");
                });
        }

        $totalSpecialists = Specialist::count();
        $totalAssignments = \DB::table('service_specialist')->count();
        $this->command->info('Total especialistas en DB: ' . $totalSpecialists);
        $this->command->info('Total asignaciones (pivot): ' . $totalAssignments);
    }
}
