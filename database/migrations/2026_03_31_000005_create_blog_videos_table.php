<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('blog_videos', function (Blueprint $table): void {
            $table->id();
            $table->string('title');
            $table->string('category')->nullable();
            $table->string('category_label')->nullable();
            $table->string('youtube_id');
            $table->boolean('is_published')->default(false);
            $table->timestamp('published_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('blog_videos');
    }
};
