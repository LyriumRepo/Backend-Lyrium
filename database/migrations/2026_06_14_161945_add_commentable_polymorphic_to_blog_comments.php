<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('blog_comments', function (Blueprint $table) {
            $table->nullableMorphs('commentable');
            $table->string('blog_post_id')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('blog_comments', function (Blueprint $table) {
            $table->dropMorphs('commentable');
            $table->integer('blog_post_id')->nullable(false)->change();
        });
    }
};
