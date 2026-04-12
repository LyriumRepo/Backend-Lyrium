<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('shipping_methods', function (Blueprint $table): void {
            $table->id();
            $table->string('code')->unique();
            $table->string('name');
            $table->text('description')->nullable();
            $table->enum('type', ['standard', 'express', 'overnight', 'pickup', 'free']);
            $table->decimal('base_cost', 10, 2)->default(0);
            $table->decimal('free_shipping_min', 10, 2)->nullable();
            $table->integer('estimated_days')->nullable();
            $table->boolean('allows_tracking')->default(true);
            $table->json('provider_config')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index('code');
            $table->index('type');
        });

        Schema::create('shipping_zones', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->string('country')->default('PE');
            $table->string('region')->nullable();
            $table->string('department')->nullable();
            $table->json('districts')->nullable();
            $table->decimal('min_weight', 10, 2)->default(0);
            $table->decimal('max_weight', 10, 2)->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index('region');
            $table->index('department');
        });

        Schema::create('shipping_rates', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('shipping_method_id')
                ->constrained('shipping_methods')
                ->cascadeOnDelete();
            $table->foreignId('zone_id')
                ->constrained('shipping_zones')
                ->cascadeOnDelete();
            $table->decimal('weight_from', 10, 2)->default(0);
            $table->decimal('weight_to', 10, 2)->nullable();
            $table->decimal('price', 10, 2);
            $table->decimal('estimated_days')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['shipping_method_id', 'zone_id']);
        });

        Schema::create('shipments', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('order_id')
                ->constrained('orders')
                ->cascadeOnDelete();
            $table->foreignId('order_item_id')
                ->nullable()
                ->constrained('order_items')
                ->nullOnDelete();
            $table->foreignId('store_id')
                ->constrained('stores')
                ->cascadeOnDelete();
            $table->foreignId('shipping_method_id')
                ->constrained('shipping_methods')
                ->cascadeOnDelete();
            $table->string('tracking_number')->nullable();
            $table->string('tracking_url')->nullable();
            $table->string('carrier')->nullable();
            $table->enum('status', ['pending', 'picked_up', 'in_transit', 'out_for_delivery', 'delivered', 'failed', 'returned'])
                ->default('pending');
            $table->text('notes')->nullable();
            $table->json('events')->nullable();
            $table->timestamp('shipped_at')->nullable();
            $table->timestamp('delivered_at')->nullable();
            $table->timestamps();

            $table->index('order_id');
            $table->index('tracking_number');
            $table->index('status');
        });

        Schema::create('store_shipping_methods', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('store_id')
                ->constrained('stores')
                ->cascadeOnDelete();
            $table->foreignId('shipping_method_id')
                ->constrained('shipping_methods')
                ->cascadeOnDelete();
            $table->boolean('is_enabled')->default(true);
            $table->decimal('additional_cost', 10, 2)->default(0);
            $table->integer('handling_time_days')->default(0);
            $table->timestamps();

            $table->unique(['store_id', 'shipping_method_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('store_shipping_methods');
        Schema::dropIfExists('shipments');
        Schema::dropIfExists('shipping_rates');
        Schema::dropIfExists('shipping_zones');
        Schema::dropIfExists('shipping_methods');
    }
};
