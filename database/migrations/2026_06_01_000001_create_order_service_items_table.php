<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('order_service_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained()->cascadeOnDelete();
            $table->foreignId('service_booking_id')->nullable()->constrained('service_bookings')->nullOnDelete();
            $table->foreignId('service_id')->constrained()->cascadeOnDelete();
            $table->foreignId('store_id')->constrained()->cascadeOnDelete();
            $table->foreignId('specialist_id')->nullable()->constrained()->nullOnDelete();

            $table->string('service_name');
            $table->unsignedInteger('quantity')->default(1);
            $table->decimal('unit_price', 10, 2);
            $table->decimal('line_total', 10, 2);
            $table->string('status')->default('pending');
            $table->dateTime('appointment_date')->nullable();
            $table->string('modality')->nullable();
            $table->unsignedInteger('duration_minutes')->nullable();
            $table->json('service_snapshot')->nullable();
            $table->string('store_name_snapshot')->nullable();
            $table->string('specialist_name_snapshot')->nullable();

            $table->timestamps();

            $table->index(['order_id', 'store_id']);
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('order_service_items');
    }
};
