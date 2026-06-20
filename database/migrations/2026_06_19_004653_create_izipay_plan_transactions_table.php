<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('izipay_plan_transactions', function (Blueprint $table): void {
            $table->id();

            $table->foreignId('plan_request_id')
                ->constrained('plan_requests')
                ->cascadeOnDelete();

            $table->foreignId('user_id')
                ->constrained('users')
                ->cascadeOnDelete();

            $table->foreignId('store_id')
                ->constrained('stores')
                ->cascadeOnDelete();

            $table->text('form_token')->nullable();
            $table->string('izipay_order_id')->nullable();
            $table->string('transaction_uuid')->nullable();

            $table->enum('status', [
                'pending',
                'paid',
                'failed',
                'refunded',
            ])->default('pending');

            $table->string('transaction_status')->nullable();
            $table->integer('amount_in_cents')->nullable();
            $table->string('currency', 3)->default('PEN');
            $table->string('payment_method_type')->nullable();
            $table->string('card_brand')->nullable();
            $table->string('card_last4', 4)->nullable();

            $table->string('kr_hash')->nullable();
            $table->json('izipay_response')->nullable();

            $table->string('error_code')->nullable();
            $table->string('error_message')->nullable();

            $table->enum('mode', ['test', 'live'])->default('test');

            $table->timestamps();

            $table->index('plan_request_id');
            $table->index('izipay_order_id');
            $table->index('status');
            $table->index('transaction_uuid');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('izipay_plan_transactions');
    }
};
