<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

/**
 * Migración 2/4 — Horarios por Especialista
 *
 * Añade `specialist_id` a `service_schedules` para que cada bloque horario
 * pertenezca a un especialista específico (no al servicio en conjunto).
 *
 * ANTES: service_schedules → (service_id, day_of_week, start_time, end_time)
 *        ↳ Todos los especialistas del servicio comparten el mismo horario.
 *
 * DESPUÉS: service_schedules → (service_id, specialist_id, day_of_week, start_time, end_time)
 *        ↳ El Dr. Juan: lunes 09:00–13:00
 *        ↳ La Dra. María: lunes 14:00–18:00 y miércoles 10:00–14:00
 *
 * ESTRATEGIA SEGURA para datos existentes (2 pasos):
 *   Paso A → Añadir specialist_id como NULLABLE (no rompe filas existentes).
 *   Paso B → Asociar automáticamente los horarios existentes al primer especialista
 *             asignado al servicio (usando service_specialist pivot).
 *             Los registros que no tengan match quedan en NULL por ahora.
 *
 * También añade `orden_bloque` para cuando un especialista tiene 2 bloques
 * el mismo día (ej: mañana y tarde).
 */
return new class extends Migration
{
    public function up(): void
    {
        // ── PASO A: Añadir columnas como nullable ──────────────────────────
        Schema::table('service_schedules', function (Blueprint $table) {

            // specialist_id: nullable inicialmente para no romper datos existentes.
            // Se posiciona justo después de service_id para coherencia semántica.
            $table->unsignedBigInteger('specialist_id')
                ->nullable()
                ->after('service_id');

            // orden_bloque: diferencia "bloque mañana" de "bloque tarde" el mismo día.
            $table->unsignedTinyInteger('orden_bloque')
                ->default(1)
                ->after('specialist_id')
                ->comment('1 = primer bloque del día, 2 = segundo bloque, etc.');
        });

        // ── PASO B: Migrar datos existentes ───────────────────────────────
        // Para cada horario sin specialist_id, buscar el primer especialista
        // asignado al mismo servicio en el pivot service_specialist.
        DB::statement("
            UPDATE service_schedules ss
            INNER JOIN (
                SELECT service_id, MIN(specialist_id) AS first_specialist_id
                FROM service_specialist
                GROUP BY service_id
            ) sp ON ss.service_id = sp.service_id
            SET ss.specialist_id = sp.first_specialist_id
            WHERE ss.specialist_id IS NULL
        ");

        // ── PASO C: Añadir la FK ahora que los datos están migrados ───────
        // Solo se añade si todos los registros tienen specialist_id resuelto.
        // Los que queden en NULL no tendrán FK constraint (nullable lo permite).
        Schema::table('service_schedules', function (Blueprint $table) {
            $table->foreign('specialist_id')
                ->references('id')
                ->on('specialists')
                ->nullOnDelete(); // Si el especialista se elimina, el horario queda huérfano pero no se borra
        });
    }

    public function down(): void
    {
        Schema::table('service_schedules', function (Blueprint $table) {
            $table->dropForeign(['specialist_id']);
            $table->dropColumn(['specialist_id', 'orden_bloque']);
        });
    }
};
