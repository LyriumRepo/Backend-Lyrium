<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('forum_topics', function (Blueprint $table) {
            $table->id();
            $table->foreignId('forum_category_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('anonymous_name')->nullable();
            $table->string('title');
            $table->text('content');
            $table->string('status')->default('active');
            $table->integer('likes_count')->default(0);
            $table->integer('love_count')->default(0);
            $table->integer('haha_count')->default(0);
            $table->integer('wow_count')->default(0);
            $table->integer('sad_count')->default(0);
            $table->integer('angry_count')->default(0);
            $table->integer('total_reactions')->default(0);
            $table->integer('reply_count')->default(0);
            $table->integer('views')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('forum_topics');
    }
};
