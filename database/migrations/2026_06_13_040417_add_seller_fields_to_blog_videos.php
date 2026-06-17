<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('blog_videos', function (Blueprint $table) {
            $table->foreignId('store_id')->nullable()->constrained()->cascadeOnDelete()->after('id');
            $table->string('platform', 30)->nullable()->after('category_label');
            $table->string('url')->nullable()->after('platform');
            $table->text('description')->nullable()->after('title');
            $table->string('thumbnail')->nullable()->after('url');
            $table->integer('duration')->nullable()->after('thumbnail');
            $table->string('status', 20)->default('draft')->after('is_published');
            $table->integer('views_count')->default(0)->after('status');
        });
    }

    public function down(): void
    {
        Schema::table('blog_videos', function (Blueprint $table) {
            $table->dropForeign(['store_id']);
            $table->dropColumn(['store_id', 'platform', 'url', 'description', 'thumbnail', 'duration', 'status', 'views_count']);
        });
    }
};
