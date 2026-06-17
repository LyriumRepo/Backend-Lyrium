<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('commission_tiers', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->decimal('min_amount', 10, 2);
            $table->decimal('max_amount', 10, 2)->nullable();
            $table->decimal('rate', 5, 2);
            $table->integer('sort_order')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('commission_tiers');
    }
};
