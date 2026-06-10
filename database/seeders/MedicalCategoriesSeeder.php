<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class MedicalCategoriesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::unprepared("

INSERT INTO `categories`
    (`parent_id`, `name`, `slug`, `description`, `image`, `sort_order`, `type`, `created_at`, `updated_at`)
VALUES
    (197, 'Cardiología',                        'servicios-cardiologia',            NULL, NULL,  8, 'service', NOW(), NOW()),
    (197, 'Radiología',                         'servicios-radiologia',             NULL, NULL,  9, 'service', NOW(), NOW()),
    (197, 'Dermatología',                       'servicios-dermatologia',           NULL, NULL, 10, 'service', NOW(), NOW()),
    (197, 'Endocrinología',                     'servicios-endocrinologia',         NULL, NULL, 11, 'service', NOW(), NOW()),
    (197, 'Enfermería',                         'servicios-enfermeria',             NULL, NULL, 12, 'service', NOW(), NOW()),
    (197, 'Ginecología',                        'servicios-ginecologia',            NULL, NULL, 13, 'service', NOW(), NOW()),
    (197, 'Medicina física y rehabilitación',   'servicios-medicina-fisica',        NULL, NULL, 14, 'service', NOW(), NOW()),
    (197, 'Neumología',                         'servicios-neumologia',             NULL, NULL, 15, 'service', NOW(), NOW()),
    (197, 'Neurología',                         'servicios-neurologia',             NULL, NULL, 16, 'service', NOW(), NOW()),
    (197, 'Odontología',                        'servicios-odontologia',            NULL, NULL, 17, 'service', NOW(), NOW()),
    (197, 'Oftalmología',                       'servicios-oftalmologia',           NULL, NULL, 18, 'service', NOW(), NOW()),
    (197, 'Oncología',                          'servicios-oncologia',              NULL, NULL, 19, 'service', NOW(), NOW()),
    (197, 'Psiquiatría',                        'servicios-psiquiatria',            NULL, NULL, 20, 'service', NOW(), NOW()),
    (197, 'Reumatología',                       'servicios-reumatologia',           NULL, NULL, 21, 'service', NOW(), NOW()),
    -- Belleza (ya existe id 209 como nivel 1, pero la imagen muestra subcategorías)
    (209, 'Peluquerías',                        'servicios-belleza-peluquerias',    NULL, NULL,  3, 'service', NOW(), NOW()),
    (209, 'Spas',                               'servicios-belleza-spas',           NULL, NULL,  4, 'service', NOW(), NOW()),
    (209, 'Otros belleza',                      'servicios-belleza-otros',          NULL, NULL,  5, 'service', NOW(), NOW()),
    -- Deportes: Gimnasios (nivel 2 hijo de 218)
    (218, 'Gimnasios',                          'servicios-deportes-gimnasios',     NULL, NULL,  4, 'service', NOW(), NOW());

-- Categorías de nivel 1 que aparecen en la imagen y pueden no existir:
-- Verificar si existen antes de insertar (ejecuta primero el SELECT)
-- SELECT id, name FROM categories WHERE type='service' AND parent_id IS NULL;

INSERT INTO `categories`
    (`parent_id`, `name`, `slug`, `description`, `image`, `sort_order`, `type`, `created_at`, `updated_at`)
SELECT NULL, 'Servicios sociales',           'servicios-sociales',           NULL, NULL, 4, 'service', NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM `categories` WHERE `slug` = 'servicios-sociales');

INSERT INTO `categories`
    (`parent_id`, `name`, `slug`, `description`, `image`, `sort_order`, `type`, `created_at`, `updated_at`)
SELECT NULL, 'Servicios para animales',      'servicios-animales',           NULL, NULL, 5, 'service', NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM `categories` WHERE `slug` = 'servicios-animales');

INSERT INTO `categories`
    (`parent_id`, `name`, `slug`, `description`, `image`, `sort_order`, `type`, `created_at`, `updated_at`)
SELECT NULL, 'Servicio de medicina natural', 'servicios-medicina-natural',   NULL, NULL, 6, 'service', NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM `categories` WHERE `slug` = 'servicios-medicina-natural');

INSERT INTO `categories`
    (`parent_id`, `name`, `slug`, `description`, `image`, `sort_order`, `type`, `created_at`, `updated_at`)
SELECT NULL, 'Alojamiento ecológico',        'servicios-alojamiento-ecologico', NULL, NULL, 7, 'service', NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM `categories` WHERE `slug` = 'servicios-alojamiento-ecologico');





        ");
    }
}
