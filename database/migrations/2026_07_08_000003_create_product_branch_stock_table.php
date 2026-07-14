<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('product_branch_stock', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->foreignId('store_branch_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('stock')->default(0);
            $table->boolean('pickup_enabled')->default(true);
            $table->timestamps();

            $table->unique(['product_id', 'store_branch_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('product_branch_stock');
    }
};
