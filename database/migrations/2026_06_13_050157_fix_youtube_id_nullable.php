<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('blog_videos', function (Blueprint $table) {
            $table->string('youtube_id')->nullable()->change();
            $table->string('category', 50)->nullable()->change();
            $table->string('category_label', 100)->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('blog_videos', function (Blueprint $table) {
            $table->string('youtube_id')->nullable(false)->change();
            $table->string('category', 50)->nullable(false)->change();
            $table->string('category_label', 100)->nullable(false)->change();
        });
    }
};
