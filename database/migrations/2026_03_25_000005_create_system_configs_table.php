<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('system_configs', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique();
            $table->string('name');
            $table->text('value')->nullable();
            $table->string('type')->default('string'); // string, color, json, boolean
            $table->string('category')->default('general'); // general, colors, seo, branding
            $table->text('description')->nullable();
            $table->boolean('is_public')->default(false);
            $table->timestamps();
        });

        Schema::table('system_configs', function (Blueprint $table) {
            $table->index('category');
            $table->index('key');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('system_configs');
    }
};
