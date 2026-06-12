<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('izipay_booking_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('service_id')->constrained()->cascadeOnDelete();
            $table->foreignId('schedule_id')->constrained('service_schedules')->cascadeOnDelete();
            $table->foreignId('specialist_id')->nullable()->constrained('specialists')->nullOnDelete();
            $table->dateTime('appointment_date');
            $table->text('customer_notes')->nullable();
            $table->string('form_token')->nullable();
            $table->string('izipay_order_id')->nullable()->unique();
            $table->string('transaction_uuid')->nullable();
            $table->string('status')->default('pending');
            $table->string('transaction_status')->nullable();
            $table->unsignedInteger('amount_in_cents');
            $table->string('currency', 3)->default('PEN');
            $table->string('payment_method_type')->nullable();
            $table->string('card_brand')->nullable();
            $table->string('card_last4', 4)->nullable();
            $table->string('error_code')->nullable();
            $table->text('error_message')->nullable();
            $table->string('mode')->nullable();
            $table->string('kr_hash')->nullable();
            $table->json('izipay_response')->nullable();
            $table->foreignId('booking_id')->nullable()->constrained('service_bookings')->nullOnDelete();
            $table->timestamps();

            $table->index(['user_id', 'status']);
            $table->index(['izipay_order_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('izipay_booking_transactions');
    }
};
