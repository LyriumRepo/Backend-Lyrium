<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('stores', function (Blueprint $table): void {
            $table->decimal('rating', 3, 2)->nullable()->default(0)->after('commission_rate');
            $table->unsignedInteger('total_sales')->default(0)->after('rating');
        });
    }

    public function down(): void
    {
        Schema::table('stores', function (Blueprint $table): void {
            $table->dropColumn(['rating', 'total_sales']);
        });
    }
};
