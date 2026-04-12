<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('invoices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained()->cascadeOnDelete();
            $table->string('invoice_number')->unique();
            $table->string('nit')->nullable();
            $table->string('business_name')->nullable();
            $table->string('provider')->default('rapifac');
            $table->string('provider_invoice_id')->nullable();
            $table->string('qr_data')->nullable();
            $table->string('pdf_url')->nullable();
            $table->string('authorization_code')->nullable();
            $table->decimal('total', 10, 2)->default(0);
            $table->string('status')->default('pending');
            $table->timestamps();

            $table->index(['order_id']);
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('invoices');
    }
};
