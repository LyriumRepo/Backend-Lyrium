<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('expenses', function (Blueprint $table) {
            $table->id();

            // Identificador legible: EXP-2024-001
            $table->string('receipt_number', 20)->unique();

            $table->foreignId('supplier_id')
                ->constrained('suppliers')
                ->restrictOnDelete();

            $table->string('concept');                          // Descripción del gasto
            $table->decimal('amount', 12, 2);                  // Monto en soles
            $table->enum('status', ['Pagado', 'Pendiente', 'Anulado'])->default('Pendiente');

            $table->date('issued_at');                          // Fecha del comprobante
            $table->date('paid_at')->nullable();               // Fecha de pago efectivo

            $table->string('voucher_type', 50)->nullable();    // Factura / Boleta / RH
            $table->string('voucher_number', 50)->nullable();  // Serie-Número
            $table->string('file_url')->nullable();            // Ruta del PDF/imagen

            // Quién registró el gasto
            $table->foreignId('registered_by')
                ->constrained('users')
                ->restrictOnDelete();

            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['supplier_id', 'status']);
            $table->index('issued_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('expenses');
    }
};
