<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Migración 1/4 — Especialistas
 *
 * Añade los campos que faltan en la tabla `specialists` según el
 * Documento de Campos del Módulo Servicios:
 *
 *  - email            → obligatorio, único. Usado SOLO para Google Calendar.
 *                       Nunca se expone al cliente (censurado en SpecialistResource).
 *  - sub_especialidad → opcional, visible al cliente.
 *  - anios_experiencia→ opcional, max 30, visible al cliente.
 *  - numero_colegiatura→ opcional, visible al cliente.
 *
 * Orden de columnas elegido para coherencia semántica:
 *   document_number → email → especialidad → sub_especialidad → anios_experiencia → numero_colegiatura
 *
 * NOTA: email es UNIQUE pero NULLABLE para no romper datos existentes.
 * Una vez que el vendedor rellene el email de cada especialista existente,
 * puede ejecutar la migración de refuerzo que lo haga NOT NULL si se desea.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('specialists', function (Blueprint $table) {

            // Email del especialista — solo para sincronización con Google Calendar.
            // Se coloca justo después de document_number.
            $table->string('email')->nullable()->unique()->after('document_number');

            // Sub-especialidad (ej: "Psicología Infantil" dentro de "Psicología").
            $table->string('sub_especialidad')->nullable()->after('especialidad');

            // Años de experiencia (0–30). Tinyint es suficiente.
            $table->unsignedTinyInteger('anios_experiencia')->nullable()->after('sub_especialidad');

            // Número de colegiatura médica/profesional.
            $table->string('numero_colegiatura', 100)->nullable()->after('anios_experiencia');
        });
    }

    public function down(): void
    {
        Schema::table('specialists', function (Blueprint $table) {
            $table->dropUnique(['email']);
            $table->dropColumn([
                'email',
                'sub_especialidad',
                'anios_experiencia',
                'numero_colegiatura',
            ]);
        });
    }
};
