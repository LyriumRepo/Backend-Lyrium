<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('blog_podcasts', function (Blueprint $table) {
            $table->foreignId('store_id')->nullable()->constrained()->cascadeOnDelete()->after('id');
            $table->string('type', 20)->default('audio')->after('store_id')->comment('audio or video');
            $table->string('platform', 30)->nullable()->after('type');
            $table->string('url')->nullable()->after('platform');
            $table->string('thumbnail')->nullable()->after('image');
            $table->json('metadata')->nullable()->after('duration');
            $table->json('tags')->nullable()->after('metadata');
            $table->string('status', 20)->default('draft')->after('tags');
            $table->integer('views_count')->default(0)->after('status');
            $table->renameColumn('image', 'cover_image');
        });
    }

    public function down(): void
    {
        Schema::table('blog_podcasts', function (Blueprint $table) {
            $table->dropForeign(['store_id']);
            $table->dropColumn(['store_id', 'type', 'platform', 'url', 'thumbnail', 'metadata', 'tags', 'status', 'views_count']);
            $table->renameColumn('cover_image', 'image');
        });
    }
};
