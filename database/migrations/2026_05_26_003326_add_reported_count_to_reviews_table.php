<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Agregar reported_count a reviews existente
        Schema::table('reviews', function (Blueprint $table) {
            $table->unsignedSmallInteger('reported_count')
                ->default(0)
                ->after('is_verified_purchase');
        });

        // Índice para el top 100 — sin esto hace full scan en products
        Schema::table('products', function (Blueprint $table) {
            $table->index(['rating_promedio', 'rating_count'], 'idx_products_rating');
        });
    }

    public function down(): void
    {
        Schema::table('reviews', function (Blueprint $table) {
            $table->dropColumn('reported_count');
        });

        Schema::table('products', function (Blueprint $table) {
            $table->dropIndex('idx_products_rating');
        });
    }
};
