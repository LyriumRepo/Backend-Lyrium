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
        Schema::table('izipay_order_transactions', function (Blueprint $table) {
            // Cambiamos la columna a LONGTEXT para soportar los extensos tokens de Izipay/Lyra
            $table->longText('form_token')->nullable()->change();

            // Opcionalmente, si tienes izipay_response como texto, nos aseguramos que aguante todo el payload
            $table->longText('izipay_response')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('izipay_order_transactions', function (Blueprint $table) {
            // Revertir a un texto clásico si se deseara
            $table->text('form_token')->nullable()->change();
            $table->json('izipay_response')->nullable()->change();
        });
    }
};
