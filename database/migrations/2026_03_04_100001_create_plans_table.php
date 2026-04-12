<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('plans', function (Blueprint $table) {
            $table->id();
            $table->string('name'); // Emprende, Crece, Especial
            $table->string('slug')->unique();
            $table->decimal('monthly_fee', 10, 2)->default(0);
            $table->decimal('commission_rate', 5, 4)->default(0.1500); // 15%
            $table->boolean('has_membership_fee')->default(false);
            $table->json('features')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('plans');
    }
};
