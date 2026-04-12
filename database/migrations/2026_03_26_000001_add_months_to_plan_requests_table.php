<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('plan_requests', function (Blueprint $table) {
            $table->integer('months')->default(1)->after('payment_method');
            $table->decimal('total_amount', 10, 2)->nullable()->after('months');
        });
    }

    public function down(): void
    {
        Schema::table('plan_requests', function (Blueprint $table) {
            $table->dropColumn(['months', 'total_amount']);
        });
    }
};
