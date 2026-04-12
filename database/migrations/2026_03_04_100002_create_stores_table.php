<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('stores', function (Blueprint $table) {
            $table->id();
            $table->foreignId('owner_id')->constrained('users')->cascadeOnDelete();
            $table->string('ruc', 11)->unique();
            $table->string('trade_name');
            $table->string('corporate_email');
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->string('logo')->nullable();
            $table->string('banner')->nullable();
            $table->string('phone', 20)->nullable();
            $table->string('status')->default('pending'); // pending, approved, rejected, banned
            $table->string('seller_type')->default('products'); // products, services, both
            $table->unsignedTinyInteger('strikes')->default(0);
            $table->decimal('commission_rate', 5, 4)->default(0.1500);
            $table->timestamp('approved_at')->nullable();
            $table->timestamp('banned_at')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stores');
    }
};
