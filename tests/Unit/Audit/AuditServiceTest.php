<?php

declare(strict_types=1);

namespace Tests\Unit\Audit;

use App\Catalogs\AuthEvents;
use App\Catalogs\OrderEvents;
use App\Catalogs\UserEvents;
use App\Events\AuditLogCreated;
use App\Events\CriticalSecurityEvent;
use App\Models\AuditLog;
use App\Models\User;
use App\Services\AuditService;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

final class AuditServiceTest extends TestCase
{
    private AuditService $auditService;

    protected function setUp(): void
    {
        parent::setUp();

        $this->setupDatabaseSchema();
        $this->truncateAllTables();
        $this->seedRoles();

        $this->auditService = app(AuditService::class);
    }

    private function setupDatabaseSchema(): void
    {
        if (Schema::hasTable('audit_logs')) {
            return;
        }

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

        Schema::create('roles', function ($table) {
            $table->id();
            $table->string('name');
            $table->string('guard_name');
            $table->timestamps();
            $table->unique(['name', 'guard_name']);
        });

        Schema::create('model_has_roles', function ($table) {
            $table->unsignedBigInteger('role_id');
            $table->string('model_type');
            $table->unsignedBigInteger('model_id');
            $table->index(['model_id', 'model_type']);
            $table->foreign('role_id')->references('id')->on('roles')->onDelete('cascade');
            $table->primary(['role_id', 'model_id', 'model_type']);
        });

        Schema::create('permissions', function ($table) {
            $table->id();
            $table->string('name');
            $table->string('guard_name');
            $table->timestamps();
            $table->unique(['name', 'guard_name']);
        });

        Schema::create('model_has_permissions', function ($table) {
            $table->unsignedBigInteger('permission_id');
            $table->string('model_type');
            $table->unsignedBigInteger('model_id');
            $table->index(['model_id', 'model_type']);
            $table->foreign('permission_id')->references('id')->on('permissions')->onDelete('cascade');
            $table->primary(['permission_id', 'model_id', 'model_type']);
        });

        Schema::create('role_has_permissions', function ($table) {
            $table->unsignedBigInteger('permission_id');
            $table->unsignedBigInteger('role_id');
            $table->foreign('permission_id')->references('id')->on('permissions')->onDelete('cascade');
            $table->foreign('role_id')->references('id')->on('roles')->onDelete('cascade');
            $table->primary(['permission_id', 'role_id']);
        });

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

    private function truncateAllTables(): void
    {
        Schema::disableForeignKeyConstraints();
        foreach (['model_has_permissions', 'role_has_permissions', 'model_has_roles', 'audit_logs', 'personal_access_tokens', 'users', 'permissions', 'roles'] as $table) {
            if (Schema::hasTable($table)) {
                \DB::table($table)->truncate();
            }
        }
        Schema::enableForeignKeyConstraints();
    }

    private function seedRoles(): void
    {
        foreach (['administrator', 'seller', 'customer', 'logistics_operator'] as $role) {
            Role::firstOrCreate(['name' => $role, 'guard_name' => 'web']);
        }
    }

    private function createAdmin(): User
    {
        $admin = User::factory()->create();
        $admin->assignRole('administrator');

        return $admin;
    }

    public function test_record_creates_audit_log_entry(): void
    {
        $log = $this->auditService->record(
            event: UserEvents::CREATED,
            module: 'users',
            description: 'Usuario creado',
        );

        $this->assertInstanceOf(AuditLog::class, $log);
        $this->assertDatabaseHas('audit_logs', [
            'id' => $log->id,
            'event' => UserEvents::CREATED,
            'module' => 'users',
            'severity' => 'info',
            'source' => 'web',
        ]);
    }

    public function test_record_with_authenticated_user(): void
    {
        $admin = $this->createAdmin();
        $this->actingAs($admin);

        $log = $this->auditService->record(
            event: UserEvents::CREATED,
            module: 'users',
            description: 'Usuario creado por admin',
        );

        $this->assertEquals($admin->id, $log->user_id);
        $this->assertEquals($admin->email, $log->user_email);
    }

    public function test_record_with_auditable_model(): void
    {
        $user = $this->createAdmin();

        $log = $this->auditService->record(
            event: UserEvents::DELETED,
            module: 'users',
            description: 'Usuario eliminado',
            auditable: $user,
        );

        $this->assertDatabaseHas('audit_logs', [
            'id' => $log->id,
            'auditable_type' => User::class,
            'auditable_id' => $user->id,
        ]);
    }

    public function test_record_with_old_and_new_values(): void
    {
        $log = $this->auditService->record(
            event: UserEvents::UPDATED,
            module: 'users',
            description: 'Rol cambiado',
            oldValues: ['role' => 'customer'],
            newValues: ['role' => 'seller'],
        );

        $this->assertDatabaseHas('audit_logs', ['id' => $log->id]);
        $this->assertIsArray($log->old_values);
        $this->assertEquals(['role' => 'customer'], $log->old_values);
        $this->assertEquals(['role' => 'seller'], $log->new_values);
    }

    public function test_record_resolves_severity_from_config(): void
    {
        $log = $this->auditService->record(
            event: UserEvents::DELETED,
            module: 'users',
            description: 'Usuario eliminado',
        );

        $this->assertEquals('critical', $log->severity);
    }

    public function test_record_override_severity(): void
    {
        $log = $this->auditService->record(
            event: UserEvents::DELETED,
            module: 'users',
            description: 'Usuario eliminado',
            severity: 'info',
        );

        $this->assertEquals('info', $log->severity);
    }

    public function test_record_default_severity_for_unknown_events(): void
    {
        $log = $this->auditService->record(
            event: OrderEvents::CREATED,
            module: 'orders',
            description: 'Pedido creado',
            severity: null,
        );

        $this->assertEquals('info', $log->severity);
    }

    public function test_record_generates_correlation_id(): void
    {
        $log = $this->auditService->record(
            event: UserEvents::CREATED,
            module: 'users',
            description: 'Usuario creado',
        );

        $this->assertNotNull($log->correlation_id);
        $this->assertTrue(Str::isUuid($log->correlation_id));
    }

    public function test_record_uses_provided_correlation_id(): void
    {
        $correlationId = (string) Str::uuid();

        $log1 = $this->auditService->record(
            event: OrderEvents::CREATED,
            module: 'orders',
            description: 'Pedido creado',
            correlationId: $correlationId,
        );

        $log2 = $this->auditService->record(
            event: UserEvents::CREATED,
            module: 'users',
            description: 'Usuario creado',
            correlationId: $correlationId,
        );

        $this->assertEquals($correlationId, $log1->correlation_id);
        $this->assertEquals($correlationId, $log2->correlation_id);
    }

    public function test_record_with_success_and_source(): void
    {
        $log = $this->auditService->record(
            event: AuthEvents::LOGIN_SUCCESS,
            module: 'auth',
            description: 'Login exitoso',
            success: true,
            source: AuditService::SOURCE_API,
        );

        $this->assertTrue($log->success);
        $this->assertEquals('api', $log->source);
    }

    public function test_record_with_metadata(): void
    {
        $log = $this->auditService->record(
            event: UserEvents::CREATED,
            module: 'users',
            description: 'Usuario creado',
            metadata: ['reason' => 'registro manual', 'source_page' => '/admin/users'],
        );

        $this->assertIsArray($log->metadata);
        $this->assertEquals('registro manual', $log->metadata['reason']);
    }

    public function test_record_dispatches_audit_log_created_event(): void
    {
        Event::fake();

        $this->auditService->record(
            event: UserEvents::CREATED,
            module: 'users',
            description: 'Usuario creado',
        );

        Event::assertDispatched(AuditLogCreated::class);
    }

    public function test_record_dispatches_critical_event_for_critical_severity(): void
    {
        Event::fake();

        $this->auditService->record(
            event: UserEvents::DELETED,
            module: 'users',
            description: 'Usuario eliminado',
        );

        Event::assertDispatched(AuditLogCreated::class);
        Event::assertDispatched(CriticalSecurityEvent::class);
    }

    public function test_record_does_not_dispatch_critical_for_info(): void
    {
        Event::fake();

        $this->auditService->record(
            event: UserEvents::CREATED,
            module: 'users',
            description: 'Usuario creado',
        );

        Event::assertDispatched(AuditLogCreated::class);
        Event::assertNotDispatched(CriticalSecurityEvent::class);
    }

    public function test_record_throws_exception_for_unknown_event_in_dev(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        $this->auditService->record(
            event: 'non.existent.event',
            module: 'test',
            description: 'Evento inválido',
        );
    }

    public function test_tracks_failed_login_pattern(): void
    {
        Event::fake();

        $threshold = (int) config('audit.patterns.failed_login.threshold', 5);

        for ($i = 0; $i < $threshold; $i++) {
            $this->auditService->record(
                event: AuthEvents::LOGIN_FAILED,
                module: 'auth',
                description: "Intento fallido {$i}",
                metadata: ['attempt' => $i],
            );
        }

        Event::assertDispatched(\App\Events\RepeatedFailedLoginEvent::class);
    }
}
