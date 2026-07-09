<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('seller_applications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('store_id')->nullable()->constrained('stores')->nullOnDelete();
            $table->string('nombre_comercial', 255);
            $table->string('ruc', 11);
            $table->string('dni', 8);
            $table->string('telefono', 20)->nullable();
            $table->string('correo', 255);
            $table->string('categoria', 255)->nullable();
            $table->string('razon_social', 255)->nullable();
            $table->json('sunat_data')->nullable();
            $table->string('tipo_evidencia', 50);
            $table->string('evidencia_valor', 500)->nullable();
            $table->tinyInteger('etapa')->default(1);
            $table->integer('score')->default(0);
            $table->string('riesgo', 10)->nullable();
            $table->string('estado', 20);
            $table->json('diagnostico')->nullable();
            $table->timestamps();

            $table->index(['estado']);
            $table->index(['ruc']);
            $table->index(['correo']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('seller_applications');
    }
};
