<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('plans', function (Blueprint $table) {
            $table->string('bg_image')->nullable()->after('compact_visible_count');
            $table->string('bg_image_fit', 20)->nullable()->after('bg_image');
            $table->string('bg_image_position', 20)->nullable()->after('bg_image_fit');
            $table->boolean('show_bg_in_card')->default(false)->after('bg_image_position');
        });
    }

    public function down(): void
    {
        Schema::table('plans', function (Blueprint $table) {
            $table->dropColumn(['bg_image', 'bg_image_fit', 'bg_image_position', 'show_bg_in_card']);
        });
    }
};
