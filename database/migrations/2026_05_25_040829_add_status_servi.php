<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Migración 4/4 — Enum status en services
 *
 * El código PHP ya acepta "draft" en StoreServiceRequest y ServiceService,
 * pero la BD solo tiene enum('active','inactive'), por lo que inserta
 * un servicio en borrador lanza un error de truncación silencioso en MySQL
 * o un error explícito en modo estricto.
 *
 * Se cambia el DEFAULT a 'draft' para que los servicios nuevos arranquen
 * como borradores hasta que el vendedor los publique explícitamente,
 * alineado con el Documento de Campos (estado: borrador o publicado).
 *
 * NOTA MySQL: Para cambiar un ENUM en MySQL/MariaDB se debe usar
 * DB::statement con ALTER TABLE MODIFY, ya que el Schema Builder
 * de Laravel no soporta modificar enums de forma nativa sin doctrine/dbal.
 */
return new class extends Migration
{
    public function up(): void
    {
        // Verificar que la columna status existe antes de modificar
        DB::statement("
            ALTER TABLE `services`
            MODIFY COLUMN `status`
            ENUM('active', 'inactive', 'draft')
            NOT NULL
            DEFAULT 'draft'
        ");
    }

    public function down(): void
    {
        // Antes de revertir, convertir borradores a inactive para no perder filas
        DB::statement("UPDATE `services` SET `status` = 'inactive' WHERE `status` = 'draft'");

        DB::statement("
            ALTER TABLE `services`
            MODIFY COLUMN `status`
            ENUM('active', 'inactive')
            NOT NULL
            DEFAULT 'active'
        ");
    }
};
