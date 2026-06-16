<?php

namespace Database\Seeders;

use App\Models\Service;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class ServiceDemoSeeder extends Seeder
{
    public function run(): void
    {
        $storeId = 1;

        $services = [
            [
                'name'                  => 'Consulta Nutricional Personalizada',
                'category_id'           => 434, // Nutriología
                'description'           => 'Evaluación integral de tu alimentación y elaboración de un plan nutricional personalizado basado en productos orgánicos y naturales. Incluye análisis de hábitos y seguimiento mensual.',
                'price'                 => 120.00,
                'duration_minutes'      => 60,
                'buffer_minutes'        => 15,
                'is_home_service'       => false,
                'booking_advance_hours' => 24,
                'max_capacity'          => 1,
                'cancellation_policy'   => 'flexible',
                'max_cancellations'     => 2,
                'benefits'              => '["Plan alimentario personalizado","Recetario con productos orgánicos","Seguimiento por 30 días","Acceso a lista de compras saludables"]',
            ],
            [
                'name'                  => 'Masaje Relajante con Aceites Naturales',
                'category_id'           => 442, // Masajes reductores
                'description'           => 'Sesión de masaje corporal completo con aceites esenciales 100% naturales de lavanda, eucalipto y árbol de té. Ideal para liberar tensiones y mejorar la circulación.',
                'price'                 => 80.00,
                'duration_minutes'      => 60,
                'buffer_minutes'        => 20,
                'is_home_service'       => true,
                'booking_advance_hours' => 12,
                'max_capacity'          => 1,
                'cancellation_policy'   => 'flexible',
                'max_cancellations'     => 2,
                'benefits'              => '["Aceites esenciales 100% naturales","Técnica sueco-andina","Reduce estrés y tensión muscular","Servicio a domicilio disponible"]',
            ],
            [
                'name'                  => 'Drenaje Linfático Manual',
                'category_id'           => 443, // Drenaje linfático
                'description'           => 'Técnica de masaje suave y rítmico que estimula el sistema linfático para eliminar toxinas y reducir la retención de líquidos. Utiliza técnica Vodder con aceites orgánicos.',
                'price'                 => 90.00,
                'duration_minutes'      => 75,
                'buffer_minutes'        => 15,
                'is_home_service'       => false,
                'booking_advance_hours' => 24,
                'max_capacity'          => 1,
                'cancellation_policy'   => 'flexible',
                'max_cancellations'     => 2,
                'benefits'              => '["Elimina toxinas y retención de líquidos","Técnica Vodder certificada","Mejora sistema inmunológico","Reduce inflamación naturalmente"]',
            ],
            [
                'name'                  => 'Sesión de Entrenamiento Funcional',
                'category_id'           => 446, // Entrenamiento personal
                'description'           => 'Entrenamiento personalizado enfocado en movimientos funcionales, fuerza y resistencia. Adaptado a tu nivel físico actual con nutrición deportiva natural incluida en la asesoría.',
                'price'                 => 70.00,
                'duration_minutes'      => 60,
                'buffer_minutes'        => 10,
                'is_home_service'       => true,
                'booking_advance_hours' => 12,
                'max_capacity'          => 1,
                'cancellation_policy'   => 'strict',
                'max_cancellations'     => 1,
                'benefits'              => '["Rutina personalizada","Asesoría nutricional deportiva","Seguimiento de progreso","Modalidad presencial o a domicilio"]',
            ],
            [
                'name'                  => 'Limpieza Facial Profunda Orgánica',
                'category_id'           => 438, // Limpieza facial
                'description'           => 'Tratamiento facial completo con productos 100% orgánicos certificados. Incluye limpieza profunda, extracción manual, mascarilla de arcilla verde y sérum hidratante natural.',
                'price'                 => 100.00,
                'duration_minutes'      => 90,
                'buffer_minutes'        => 20,
                'is_home_service'       => false,
                'booking_advance_hours' => 24,
                'max_capacity'          => 1,
                'cancellation_policy'   => 'flexible',
                'max_cancellations'     => 2,
                'benefits'              => '["Productos 100% orgánicos certificados","Extracción profesional","Mascarilla de arcilla verde","Sérum hidratante natural incluido"]',
            ],
            [
                'name'                  => 'Evaluación Física y Plan de Salud',
                'category_id'           => 448, // Evaluación física
                'description'           => 'Evaluación completa de tu condición física: composición corporal, fuerza, flexibilidad y resistencia cardiovascular. Incluye plan de acción con enfoque holístico y natural.',
                'price'                 => 85.00,
                'duration_minutes'      => 90,
                'buffer_minutes'        => 15,
                'is_home_service'       => false,
                'booking_advance_hours' => 48,
                'max_capacity'          => 1,
                'cancellation_policy'   => 'flexible',
                'max_cancellations'     => 2,
                'benefits'              => '["Análisis de composición corporal","Medición IMC y porcentaje graso","Plan de salud holístico","Reporte digital de resultados"]',
            ],
            [
                'name'                  => 'Sesión de Psicología Integrativa',
                'category_id'           => 435, // Psicología
                'description'           => 'Atención psicológica con enfoque integrativo que combina terapia cognitivo-conductual con técnicas de mindfulness y bienestar holístico. Ambiente seguro y empático.',
                'price'                 => 110.00,
                'duration_minutes'      => 60,
                'buffer_minutes'        => 15,
                'is_home_service'       => false,
                'booking_advance_hours' => 24,
                'max_capacity'          => 1,
                'cancellation_policy'   => 'strict',
                'max_cancellations'     => 1,
                'benefits'              => '["Enfoque cognitivo-conductual","Técnicas de mindfulness","Sesión confidencial y empática","Seguimiento entre sesiones"]',
            ],
            [
                'name'                  => 'Tratamiento Antiaging Facial Natural',
                'category_id'           => 440, // Antiaging
                'description'           => 'Tratamiento facial rejuvenecedor con vitamina C natural, ácido hialurónico vegetal y extractos de plantas peruanas. Estimula la producción de colágeno de forma natural.',
                'price'                 => 150.00,
                'duration_minutes'      => 90,
                'buffer_minutes'        => 20,
                'is_home_service'       => false,
                'booking_advance_hours' => 48,
                'max_capacity'          => 1,
                'cancellation_policy'   => 'flexible',
                'max_cancellations'     => 2,
                'benefits'              => '["Vitamina C natural 100%","Ácido hialurónico vegetal","Extractos de plantas peruanas","Estimulación de colágeno natural"]',
            ],
            [
                'name'                  => 'Fisioterapia y Rehabilitación Natural',
                'category_id'           => 449, // Fisioterapia deportiva
                'description'           => 'Sesión de fisioterapia especializada para lesiones deportivas y dolor crónico. Combina técnicas manuales con termoterapia y aplicación de ungüentos naturales antiinflamatorios.',
                'price'                 => 95.00,
                'duration_minutes'      => 60,
                'buffer_minutes'        => 15,
                'is_home_service'       => true,
                'booking_advance_hours' => 24,
                'max_capacity'          => 1,
                'cancellation_policy'   => 'flexible',
                'max_cancellations'     => 2,
                'benefits'              => '["Técnicas manuales especializadas","Termoterapia natural","Ungüentos antiinflamatorios orgánicos","Disponible a domicilio"]',
            ],
            [
                'name'                  => 'Taller de Nutrición Familiar Bio',
                'category_id'           => 434, // Nutriología
                'description'           => 'Taller grupal para familias sobre alimentación saludable, orgánica y asequible. Aprende a planificar menús semanales con productos bio, leer etiquetas y cocinar de forma nutritiva.',
                'price'                 => 45.00,
                'duration_minutes'      => 120,
                'buffer_minutes'        => 30,
                'is_home_service'       => false,
                'booking_advance_hours' => 72,
                'max_capacity'          => 8,
                'cancellation_policy'   => 'no_refund',
                'max_cancellations'     => 0,
                'benefits'              => '["Menús semanales saludables","Guía de compras orgánicas","Recetario digital incluido","Hasta 8 personas por grupo"]',
            ],
        ];

        foreach ($services as $data) {
            $slug = Str::slug($data['name']);
            $counter = 1;
            $baseSlug = $slug;
            while (Service::where('slug', $slug)->exists()) {
                $slug = $baseSlug . '-' . $counter++;
            }

            Service::create([
                'store_id'              => $storeId,
                'category_id'           => $data['category_id'],
                'name'                  => $data['name'],
                'slug'                  => $slug,
                'description'           => $data['description'],
                'benefits'              => $data['benefits'],
                'price'                 => $data['price'],
                'duration_minutes'      => $data['duration_minutes'],
                'buffer_minutes'        => $data['buffer_minutes'],
                'is_home_service'       => $data['is_home_service'],
                'booking_advance_hours' => $data['booking_advance_hours'],
                'max_capacity'          => $data['max_capacity'],
                'status'                => Service::STATUS_ACTIVE,
                'cancellation_policy'   => $data['cancellation_policy'],
                'max_cancellations'     => $data['max_cancellations'],
            ]);
        }

        $this->command->info('✓ 10 servicios demo creados para BioTienda Demo (store_id=1)');
    }
}
