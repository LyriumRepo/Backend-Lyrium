<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * ARCHIVO: database/migrations/2026_05_25_000001_create_izipay_order_transactions_table.php
 *
 * Registra cada sesión de pago de órdenes via Izipay.
 * (La tabla izipay de planes ya existe en plan_requests — esta es solo para órdenes de compra)
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('izipay_order_transactions', function (Blueprint $table): void {
            $table->id();

            $table->foreignId('order_id')
                ->constrained('orders')
                ->cascadeOnDelete();

            $table->foreignId('user_id')
                ->constrained('users')
                ->cascadeOnDelete();

            // Datos de la sesión de pago
            $table->string('form_token')->nullable();          // Token enviado al Smart Form
            $table->string('izipay_order_id')->nullable();     // orderId que enviamos a Izipay
            $table->string('transaction_uuid')->nullable();    // UUID devuelto por Izipay al pagar

            // Estado
            $table->enum('status', [
                'pending',   // formToken generado, esperando pago
                'paid',      // Izipay confirmó el pago (webhook recibido)
                'failed',    // Pago rechazado o expirado
                'refunded',  // Reembolsado
            ])->default('pending');

            // Datos del pago (llegan en el webhook de Izipay)
            $table->string('transaction_status')->nullable();  // AUTHORISED, CAPTURED, REFUSED
            $table->integer('amount_in_cents')->nullable();    // Monto en céntimos (14990 = S/149.90)
            $table->string('currency', 3)->default('PEN');
            $table->string('payment_method_type')->nullable(); // CARD, YAPE, PLIN, etc.
            $table->string('card_brand')->nullable();          // VISA, MASTERCARD
            $table->string('card_last4', 4)->nullable();

            // Seguridad: hash para verificar que el webhook viene de Izipay
            $table->string('kr_hash')->nullable();

            // Respuesta completa de Izipay (para auditoría y debugging)
            $table->json('izipay_response')->nullable();

            // Error si falló
            $table->string('error_code')->nullable();
            $table->string('error_message')->nullable();

            $table->enum('mode', ['test', 'live'])->default('test');

            $table->timestamps();

            $table->index('order_id');
            $table->index('izipay_order_id');
            $table->index('status');
            $table->index('transaction_uuid');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('izipay_order_transactions');
    }
};
