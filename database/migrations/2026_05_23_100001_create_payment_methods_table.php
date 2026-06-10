<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payment_methods', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('tipo_metodo'); // tarjeta, yape, plin
            $table->string('documento');
            $table->string('titular');
            $table->string('detalle_extra')->nullable();
            $table->boolean('is_default')->default(false);
            $table->string('ruc_dni')->nullable();
            $table->string('razon_social')->nullable();
            $table->string('direccion_fiscal')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payment_methods');
    }
};
