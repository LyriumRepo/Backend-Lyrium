<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table): void {
            $table->enum('type', ['physical', 'digital', 'service'])->default('physical')->after('status');
            $table->string('short_description', 300)->nullable()->after('description');
            $table->string('sku', 100)->nullable()->unique()->after('type');
            $table->decimal('regular_price', 10, 2)->nullable()->after('price');
            $table->decimal('sale_price', 10, 2)->nullable()->after('regular_price');
            $table->decimal('rating_promedio', 3, 2)->default(0)->after('sale_price');
            $table->unsignedInteger('rating_count')->default(0)->after('rating_promedio');
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table): void {
            $table->dropColumn([
                'type',
                'short_description',
                'sku',
                'regular_price',
                'sale_price',
                'rating_promedio',
                'rating_count',
            ]);
        });
    }
};
