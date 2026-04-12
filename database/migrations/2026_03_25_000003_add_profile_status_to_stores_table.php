<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('stores', function (Blueprint $table) {
            $table->string('profile_status')->default('approved')->after('gallery');
            $table->timestamp('profile_updated_at')->nullable()->after('profile_status');
        });
    }

    public function down(): void
    {
        Schema::table('stores', function (Blueprint $table) {
            $table->dropColumn(['profile_status', 'profile_updated_at']);
        });
    }
};
