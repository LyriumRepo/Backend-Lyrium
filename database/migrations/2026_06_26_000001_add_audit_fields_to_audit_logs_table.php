<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('audit_logs', function (Blueprint $table) {
            $table->string('severity', 10)->default('info')->after('module');
            $table->string('session_id', 100)->nullable()->after('user_role');
            $table->string('correlation_id', 100)->nullable()->after('session_id');
            $table->boolean('success')->nullable()->after('description');
            $table->string('source', 15)->default('web')->after('success');
            $table->string('request_method', 10)->nullable()->after('source');
            $table->string('request_url', 500)->nullable()->after('request_method');
            $table->unsignedSmallInteger('response_code')->nullable()->after('request_url');
            $table->json('metadata')->nullable()->after('new_values');

            $table->index(['severity', 'created_at'], 'idx_severity_created');
            $table->index('ip_address', 'idx_ip_address');
            $table->index('session_id', 'idx_session_id');
            $table->index('correlation_id', 'idx_correlation_id');
            $table->index(['event', 'created_at'], 'idx_event_created');
        });
    }

    public function down(): void
    {
        Schema::table('audit_logs', function (Blueprint $table) {
            $table->dropIndex('idx_severity_created');
            $table->dropIndex('idx_ip_address');
            $table->dropIndex('idx_session_id');
            $table->dropIndex('idx_correlation_id');
            $table->dropIndex('idx_event_created');

            $table->dropColumn([
                'severity',
                'session_id',
                'correlation_id',
                'success',
                'source',
                'request_method',
                'request_url',
                'response_code',
                'metadata',
            ]);
        });
    }
};
