<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Limpia los valores corruptos de start_time y end_time en service_schedules.
 *
 * El bug: el cast 'datetime:H:i' guardaba "2026-05-18 09:00:00" en vez de "09:00".
 * Esta migration extrae solo la parte HH:MM de todos los registros existentes.
 *
 * Nombre: 2026_05_18_fix_service_schedules_time_columns.php
 * Ejecutar: php artisan migrate
 */
return new class extends Migration
{
    public function up(): void
    {
        // Cambiar columnas a TIME para que la DB rechace fechas completas en el futuro
        Schema::table('service_schedules', function (Blueprint $table) {
            $table->time('start_time')->change();
            $table->time('end_time')->change();
        });

        // Limpiar datos corruptos: extraer solo HH:MM:SS de valores que vengan
        // como "2026-05-18 09:00:00" (datetime completo guardado por el bug)
        DB::statement("
            UPDATE service_schedules
            SET start_time = TIME(start_time)
            WHERE start_time LIKE '____-__-__ %'
               OR start_time LIKE '____-%'
        ");

        DB::statement("
            UPDATE service_schedules
            SET end_time = TIME(end_time)
            WHERE end_time LIKE '____-__-__ %'
               OR end_time LIKE '____-%'
        ");
    }

    public function down(): void
    {
        Schema::table('service_schedules', function (Blueprint $table) {
            $table->string('start_time')->change();
            $table->string('end_time')->change();
        });
    }
};
