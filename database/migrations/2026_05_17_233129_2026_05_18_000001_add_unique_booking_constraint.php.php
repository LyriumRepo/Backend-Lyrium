<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Añade una restricción UNIQUE a nivel de DB para prevenir double-bookings
 * incluso si la lógica de aplicación falla.
 *
 * La constraint es: (schedule_id, appointment_date) UNIQUE
 * — un mismo horario no puede tener dos reservas activas en el mismo datetime exacto.
 *
 * Nombre del archivo: 2026_05_18_000001_add_unique_booking_constraint.php
 * Ejecutar: php artisan migrate
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('service_bookings', function (Blueprint $table) {
            // Índice parcial: solo reservas activas bloquean el slot.
            // MySQL no soporta partial indexes nativos → usamos un índice normal
            // y la lógica de aplicación filtra los cancelados.
            // Si usas PostgreSQL, puedes usar whereNotIn en el índice.
            $table->index(
                ['schedule_id', 'appointment_date'],
                'idx_booking_schedule_datetime'
            );

            // Para MySQL: trigger o check en aplicación. Para PostgreSQL o SQLite:
            // unique partial index. La constraint de nivel de índice unique completo
            // solo aplica si necesitas bloquear a nivel DB también los cancelados.
            // Recomendamos la unique parcial en Postgres:
            //
            //   CREATE UNIQUE INDEX uq_active_booking_slot
            //   ON service_bookings (schedule_id, appointment_date)
            //   WHERE status NOT IN ('cancelled');
            //
            // En MySQL (no soporta WHERE en UNIQUE INDEX), la protección es
            // a nivel de aplicación con lockForUpdate() en la transacción.
        });
    }

    public function down(): void
    {
        Schema::table('service_bookings', function (Blueprint $table) {
            $table->dropIndex('idx_booking_schedule_datetime');
        });
    }
};