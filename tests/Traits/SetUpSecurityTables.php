<?php

declare(strict_types=1);

namespace Tests\Traits;

use Illuminate\Support\Facades\Schema;

trait SetUpSecurityTables
{
    protected function setUpSecurityTables(): void
    {
        Schema::disableForeignKeyConstraints();

        $tables = ['blocked_ips', 'security_alerts', 'protection_rules', 'audit_logs', 'personal_access_tokens', 'model_has_permissions', 'role_has_permissions', 'model_has_roles', 'permissions', 'roles', 'users'];

        foreach ($tables as $table) {
            if (Schema::hasTable($table)) {
                \DB::table($table)->truncate();
            }
        }

        Schema::enableForeignKeyConstraints();

        if (!Schema::hasTable('users')) {
            Schema::create('users', function ($table) {
                $table->id();
                $table->string('name');
                $table->string('username')->nullable()->unique();
                $table->string('email')->unique();
                $table->string('nicename', 100)->nullable();
                $table->timestamp('email_verified_at')->nullable();
                $table->string('password');
                $table->rememberToken();
                $table->timestamps();
                $table->softDeletes();
            });
        }

        if (!Schema::hasTable('personal_access_tokens')) {
            Schema::create('personal_access_tokens', function ($table) {
                $table->id();
                $table->morphs('tokenable');
                $table->string('name');
                $table->string('token', 64)->unique();
                $table->text('abilities')->nullable();
                $table->timestamp('last_used_at')->nullable();
                $table->timestamp('expires_at')->nullable();
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('roles')) {
            Schema::create('roles', function ($table) {
                $table->id();
                $table->string('name');
                $table->string('guard_name');
                $table->timestamps();
                $table->unique(['name', 'guard_name']);
            });
        }

        if (!Schema::hasTable('model_has_roles')) {
            Schema::create('model_has_roles', function ($table) {
                $table->unsignedBigInteger('role_id');
                $table->string('model_type');
                $table->unsignedBigInteger('model_id');
                $table->index(['model_id', 'model_type']);
                $table->foreign('role_id')->references('id')->on('roles')->onDelete('cascade');
                $table->primary(['role_id', 'model_id', 'model_type']);
            });
        }

        if (!Schema::hasTable('permissions')) {
            Schema::create('permissions', function ($table) {
                $table->id();
                $table->string('name');
                $table->string('guard_name');
                $table->timestamps();
                $table->unique(['name', 'guard_name']);
            });
        }

        if (!Schema::hasTable('model_has_permissions')) {
            Schema::create('model_has_permissions', function ($table) {
                $table->unsignedBigInteger('permission_id');
                $table->string('model_type');
                $table->unsignedBigInteger('model_id');
                $table->index(['model_id', 'model_type']);
                $table->foreign('permission_id')->references('id')->on('permissions')->onDelete('cascade');
                $table->primary(['permission_id', 'model_id', 'model_type']);
            });
        }

        if (!Schema::hasTable('role_has_permissions')) {
            Schema::create('role_has_permissions', function ($table) {
                $table->unsignedBigInteger('permission_id');
                $table->unsignedBigInteger('role_id');
                $table->foreign('permission_id')->references('id')->on('permissions')->onDelete('cascade');
                $table->foreign('role_id')->references('id')->on('roles')->onDelete('cascade');
                $table->primary(['permission_id', 'role_id']);
            });
        }

        if (!Schema::hasTable('audit_logs')) {
            Schema::create('audit_logs', function ($table) {
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
            });
        }

        if (!Schema::hasTable('blocked_ips')) {
            Schema::create('blocked_ips', function ($table) {
                $table->id();
                $table->string('ip_address', 45)->unique();
                $table->string('reason')->nullable();
                $table->foreignId('blocked_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamp('blocked_at')->nullable();
                $table->timestamp('expires_at')->nullable();
                $table->timestamp('unblocked_at')->nullable();
                $table->foreignId('unblocked_by')->nullable()->constrained('users')->nullOnDelete();
                $table->string('status', 20)->default('blocked');
                $table->index('status');
                $table->index(['status', 'expires_at']);
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('security_alerts')) {
            Schema::create('security_alerts', function ($table) {
                $table->id();
                $table->foreignId('audit_log_id')->nullable()->constrained('audit_logs')->nullOnDelete();
                $table->string('type', 50)->default('critical');
                $table->string('title');
                $table->text('message')->nullable();
                $table->string('severity', 10)->default('critical');
                $table->string('status', 20)->default('open');
                $table->string('ip_address', 45)->nullable();
                $table->timestamp('created_at')->useCurrent();
                $table->timestamp('resolved_at')->nullable();
                $table->foreignId('resolved_by')->nullable()->constrained('users')->nullOnDelete();
                $table->index('status');
                $table->index(['severity', 'created_at']);
            });
        }

        if (!Schema::hasTable('protection_rules')) {
            Schema::create('protection_rules', function ($table) {
                $table->id();
                $table->string('name');
                $table->string('type', 30);
                $table->string('pattern')->nullable();
                $table->string('severity', 10)->default('critical');
                $table->string('status', 20)->default('active');
                $table->integer('priority')->default(0);
                $table->text('description')->nullable();
                $table->json('config')->nullable();
                $table->timestamp('triggered_at')->nullable();
                $table->integer('trigger_count')->default(0);
                $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('system_configs')) {
            Schema::create('system_configs', function ($table) {
                $table->id();
                $table->string('key')->unique();
                $table->string('name')->nullable();
                $table->text('value')->nullable();
                $table->string('type', 20)->default('string');
                $table->string('category', 50)->default('general');
                $table->text('description')->nullable();
                $table->boolean('is_public')->default(false);
                $table->timestamps();
            });
        }

        \App\Models\SystemConfig::firstOrCreate(
            ['key' => 'autoblock_enabled'],
            ['key' => 'autoblock_enabled', 'value' => 'true', 'type' => 'boolean', 'category' => 'security', 'name' => 'Auto-bloqueo habilitado', 'description' => '', 'is_public' => false],
        );
        \App\Models\SystemConfig::firstOrCreate(
            ['key' => 'autoblock_threshold'],
            ['key' => 'autoblock_threshold', 'value' => '10', 'type' => 'integer', 'category' => 'security', 'name' => 'Umbral', 'description' => '', 'is_public' => false],
        );
        \App\Models\SystemConfig::firstOrCreate(
            ['key' => 'autoblock_window_minutes'],
            ['key' => 'autoblock_window_minutes', 'value' => '10', 'type' => 'integer', 'category' => 'security', 'name' => 'Ventana', 'description' => '', 'is_public' => false],
        );
        \App\Models\SystemConfig::firstOrCreate(
            ['key' => 'autoblock_duration_minutes'],
            ['key' => 'autoblock_duration_minutes', 'value' => '20', 'type' => 'integer', 'category' => 'security', 'name' => 'Duración', 'description' => '', 'is_public' => false],
        );
    }
}
