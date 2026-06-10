<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('addresses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('etiqueta'); // casa, trabajo, otro
            $table->string('destinatario');
            $table->string('pais')->default('Perú');
            $table->string('departamento');
            $table->string('provincia');
            $table->string('distrito');
            $table->string('avenida');
            $table->string('numero');
            $table->string('piso_lote')->nullable();
            $table->string('referencia')->nullable();
            $table->boolean('is_default')->default(false);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('addresses');
    }
};
