<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('subscriptions', function (Blueprint $table) {
            $table->boolean('auto_renew')->default(false)->after('status');
            $table->foreignId('payment_method_id')->nullable()->after('auto_renew')
                ->constrained('payment_methods')->nullOnDelete();
            $table->foreignId('plan_request_id')->nullable()->after('payment_method_id')
                ->constrained('plan_requests')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('subscriptions', function (Blueprint $table) {
            $table->dropConstrainedForeignId('plan_request_id');
            $table->dropConstrainedForeignId('payment_method_id');
            $table->dropColumn('auto_renew');
        });
    }
};
