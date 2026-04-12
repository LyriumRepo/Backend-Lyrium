<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('stores', function (Blueprint $table) {
            $table->string('whatsapp', 50)->nullable()->after('tiktok');
            $table->string('youtube', 255)->nullable()->after('whatsapp');
            $table->string('twitter', 255)->nullable()->after('youtube');
            $table->string('linkedin', 255)->nullable()->after('twitter');
            $table->string('website', 255)->nullable()->after('linkedin');
        });
    }

    public function down(): void
    {
        Schema::table('stores', function (Blueprint $table) {
            $table->dropColumn(['whatsapp', 'youtube', 'twitter', 'linkedin', 'website']);
        });
    }
};
