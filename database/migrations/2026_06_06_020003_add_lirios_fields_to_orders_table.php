<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->integer('lirios_used')->nullable()->after('discount_amount');
            $table->decimal('lirios_discount', 10, 2)->default(0)->after('lirios_used');
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn(['lirios_used', 'lirios_discount']);
        });
    }
};
