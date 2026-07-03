<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('audit_logs_archived', function (Blueprint $table) {
            $table->id();

            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('user_email')->nullable();
            $table->string('user_role', 50)->nullable();
            $table->string('session_id', 100)->nullable();
            $table->string('correlation_id', 100)->nullable();

            $table->string('event', 100);
            $table->string('module', 80);
            $table->string('severity', 10)->default('info');
            $table->string('description');
            $table->boolean('success')->nullable();
            $table->string('source', 15)->default('web');

            $table->string('auditable_type')->nullable();
            $table->unsignedBigInteger('auditable_id')->nullable();

            $table->json('old_values')->nullable();
            $table->json('new_values')->nullable();
            $table->json('metadata')->nullable();

            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->string('request_method', 10)->nullable();
            $table->string('request_url', 500)->nullable();
            $table->unsignedSmallInteger('response_code')->nullable();

            $table->timestamp('created_at')->useCurrent();

            $table->index(['module', 'event']);
            $table->index(['severity', 'created_at']);
            $table->index('ip_address');
            $table->index('correlation_id');
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('audit_logs_archived');
    }
};
