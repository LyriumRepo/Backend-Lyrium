<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Drop FK if it exists before changing the column type
        try {
            Schema::table('blog_comments', function (Blueprint $table) {
                $table->dropForeign(['blog_post_id']);
            });
        } catch (\Throwable $e) {
            // FK didn't exist, continue
        }

        Schema::table('blog_comments', function (Blueprint $table) {
            $table->string('blog_post_id')->nullable()->change();

            if (!Schema::hasColumn('blog_comments', 'commentable_type')) {
                $table->nullableMorphs('commentable');
            }
        });
    }

    public function down(): void
    {
        Schema::table('blog_comments', function (Blueprint $table) {
            $table->dropForeign(['blog_post_id']);
            $table->dropMorphs('commentable');
            $table->integer('blog_post_id')->nullable(false)->change();
            $table->foreign('blog_post_id')->references('id')->on('blog_articles')->onDelete('cascade');
        });
    }
};
