<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('forum_posts', function (Blueprint $table) {
            $table->foreignId('store_id')->nullable()->constrained()->cascadeOnDelete()->after('id');
            $table->foreignId('hidden_by')->nullable()->constrained('users')->nullOnDelete()->after('status');
            $table->timestamp('hidden_at')->nullable()->after('hidden_by');
            $table->integer('report_count')->default(0)->after('hidden_at');
            $table->timestamp('last_report_at')->nullable()->after('report_count');
        });
    }

    public function down(): void
    {
        Schema::table('forum_posts', function (Blueprint $table) {
            $table->dropForeign(['store_id']);
            $table->dropForeign(['hidden_by']);
            $table->dropColumn(['store_id', 'hidden_by', 'hidden_at', 'report_count', 'last_report_at']);
        });
    }
};
