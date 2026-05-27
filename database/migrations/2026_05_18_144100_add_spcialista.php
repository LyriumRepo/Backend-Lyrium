<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. Tabla de Especialistas
        Schema::create('specialists', function (Blueprint $table) {
            $table->id();
            $table->foreignId('store_id')->constrained()->onDelete('cascade');
            $table->string('nombres');
            $table->string('apellidos');
            $table->string('document_type')->default('DNI');
            $table->string('document_number');
            $table->string('especialidad');
            $table->string('availability')->default('Disponible'); // Disponible / Indispuesto / Ocupado
            $table->string('foto')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        // 2. Tabla Pivote de Relación Muchos a Muchos
        Schema::create('service_specialist', function (Blueprint $table) {
            $table->id();
            $table->foreignId('service_id')->constrained()->onDelete('cascade');
            $table->foreignId('specialist_id')->constrained()->onDelete('cascade');
            $table->timestamps();
        });

        // 3. Modificar Tabla de Servicios para Añadir Campos del Frontend
        Schema::table('services', function (Blueprint $table) {
            $table->integer('buffer_minutes')->default(10)->after('duration_minutes');
            $table->boolean('is_home_service')->default(false)->after('buffer_minutes');
            $table->integer('booking_advance_hours')->default(24)->after('is_home_service');
            $table->integer('max_capacity')->default(1)->after('booking_advance_hours'); // Cupos
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('service_specialist');
        Schema::dropIfExists('specialists');

        Schema::table('services', function (Blueprint $table) {
            $table->dropColumn(['buffer_minutes', 'is_home_service', 'booking_advance_hours', 'max_capacity']);
        });
    }
};
