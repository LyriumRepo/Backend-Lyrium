<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * ARCHIVO: database/migrations/2026_05_23_000001_create_culqi_transactions_table.php
 *
 * Guarda cada intento de cobro hecho a través de Culqi.
 * Permite trazabilidad completa: cobros exitosos, fallidos y rechazados.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('culqi_transactions', function (Blueprint $table): void {
            $table->id();

            // Relación con la orden que se está pagando
            $table->foreignId('order_id')
                ->constrained('orders')
                ->cascadeOnDelete();

            // Relación con el usuario que paga
            $table->foreignId('user_id')
                ->constrained('users')
                ->cascadeOnDelete();

            // Datos que devuelve Culqi al hacer el cargo
            $table->string('culqi_charge_id')->nullable()->unique();  // ch_live_xxx o ch_test_xxx
            $table->string('culqi_token')->nullable();                 // tkn_live_xxx (no guardar datos de tarjeta)
            $table->string('culqi_order_id')->nullable();             // ord_live_xxx si usas Culqi Orders

            // Estado del cobro
            $table->enum('status', [
                'pending',    // Intento iniciado, esperando respuesta de Culqi
                'paid',       // Culqi aprobó el cobro
                'failed',     // Culqi rechazó el cobro
                'refunded',   // Cobro revertido
            ])->default('pending');

            // Montos (Culqi trabaja en céntimos, nosotros en soles)
            $table->decimal('amount', 10, 2);         // Ej: 61.50
            $table->integer('amount_in_cents');        // Ej: 6150 (lo que se envía a Culqi)
            $table->string('currency', 3)->default('PEN');

            // Datos de la tarjeta (solo metadata — nunca el número completo)
            $table->string('card_brand')->nullable();          // visa, mastercard, amex
            $table->string('card_last_four', 4)->nullable();   // últimos 4 dígitos
            $table->string('card_exp_month', 2)->nullable();
            $table->string('card_exp_year', 4)->nullable();

            // Email del pagador enviado a Culqi
            $table->string('email')->nullable();

            // Respuesta completa de Culqi (para debugging y auditoría)
            $table->json('culqi_response')->nullable();

            // Mensaje de error si falló
            $table->string('error_code')->nullable();     // Ej: card_declined
            $table->string('error_message')->nullable();  // Ej: "Tarjeta declinada"

            // Origen del cobro
            $table->enum('source', ['checkout', 'webhook'])->default('checkout');

            // Modo de operación
            $table->enum('mode', ['test', 'live'])->default('test');

            $table->timestamps();

            // Índices para consultas frecuentes
            $table->index('order_id');
            $table->index('status');
            $table->index('culqi_charge_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('culqi_transactions');
    }
};
