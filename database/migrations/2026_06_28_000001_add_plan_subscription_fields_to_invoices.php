<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            $table->foreignId('plan_request_id')->nullable()->constrained('plan_requests')->nullOnDelete()->after('order_id');
            $table->string('source', 50)->default('order')->after('plan_request_id');
            $table->index('source');
        });
    }

    public function down(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            $table->dropForeign(['plan_request_id']);
            $table->dropColumn(['plan_request_id', 'source']);
        });
    }
};
