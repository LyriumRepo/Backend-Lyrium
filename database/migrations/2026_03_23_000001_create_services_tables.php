<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('services', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('store_id')
                ->constrained()
                ->cascadeOnDelete();
            $table->string('name');
            $table->text('description')->nullable();
            $table->decimal('price', 10, 2);
            $table->integer('duration_minutes')->default(60);
            $table->enum('status', ['active', 'inactive'])->default('active');
            $table->enum('cancellation_policy', ['no_refund', 'flexible', 'strict'])->default('flexible');
            $table->integer('max_cancellations')->default(3);
            $table->json('settings')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index('store_id');
            $table->index('status');
        });

        Schema::create('service_schedules', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('service_id')
                ->constrained('services')
                ->cascadeOnDelete();
            $table->enum('day_of_week', ['monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday']);
            $table->time('start_time');
            $table->time('end_time');
            $table->integer('max_appointments')->default(10);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['service_id', 'day_of_week']);
        });

        Schema::create('service_bookings', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('service_id')
                ->constrained('services')
                ->cascadeOnDelete();
            $table->foreignId('user_id')
                ->constrained()
                ->cascadeOnDelete();
            $table->foreignId('schedule_id')
                ->constrained('service_schedules')
                ->cascadeOnDelete();
            $table->datetime('appointment_date');
            $table->enum('status', ['pending', 'confirmed', 'completed', 'cancelled', 'no_show'])
                ->default('pending');
            $table->decimal('total_price', 10, 2);
            $table->string('payment_method')->nullable();
            $table->string('payment_status', 20)->default('pending');
            $table->text('customer_notes')->nullable();
            $table->text('seller_notes')->nullable();
            $table->string('reschedule_token', 64)->nullable();
            $table->timestamp('confirmed_at')->nullable();
            $table->timestamp('cancelled_at')->nullable();
            $table->timestamps();

            $table->index('service_id');
            $table->index('user_id');
            $table->index('appointment_date');
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('service_bookings');
        Schema::dropIfExists('service_schedules');
        Schema::dropIfExists('services');
    }
};
