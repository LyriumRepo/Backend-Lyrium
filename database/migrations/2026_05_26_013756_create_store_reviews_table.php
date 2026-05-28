<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('store_reviews', function (Blueprint $table) {
            $table->id();

            $table->foreignId('store_id')
                ->constrained('stores')
                ->cascadeOnDelete();

            $table->foreignId('user_id')
                ->constrained('users')
                ->cascadeOnDelete();

            $table->foreignId('order_id')
                ->nullable()
                ->constrained('orders')
                ->nullOnDelete();

            $table->tinyInteger('rating')->unsigned(); // 1-5

            // Criterios específicos de la tienda (opcionales)
            $table->tinyInteger('rating_communication')->unsigned()->nullable(); // atención
            $table->tinyInteger('rating_shipping')->unsigned()->nullable();      // envío
            $table->tinyInteger('rating_packaging')->unsigned()->nullable();     // empaque

            $table->string('title', 150)->nullable();
            $table->text('comment')->nullable();
            $table->boolean('is_verified_purchase')->default(false);
            $table->unsignedSmallInteger('reported_count')->default(0);

            $table->timestamps();

            // Un usuario solo puede reseñar una tienda una vez
            $table->unique(['store_id', 'user_id'], 'uq_store_user_review');

            $table->index('store_id');
            $table->index('rating');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('store_reviews');
    }
};
