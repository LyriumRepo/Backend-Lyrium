<?php

declare(strict_types=1);

namespace Tests\Feature\Security;

use App\Events\RepeatedFailedLoginEvent;
use App\Listeners\AutoBlockIpListener;
use App\Models\AuditLog;
use App\Models\BlockedIp;
use App\Models\SecurityAlert;
use App\Services\AuditService;
use Tests\TestCase;
use Tests\Traits\SetUpSecurityTables;
use Tests\Traits\WithRoles;

final class AutoBlockIpListenerTest extends TestCase
{
    use SetUpSecurityTables, WithRoles;

    private AutoBlockIpListener $listener;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpSecurityTables();
        $this->seedRoles();
        $this->listener = new AutoBlockIpListener(app(AuditService::class));
    }

    public function test_blocks_ip_on_repeated_failed_login(): void
    {
        $event = new RepeatedFailedLoginEvent(new AuditLog(), '192.168.1.100', 10);

        $this->listener->handle($event);

        $this->assertDatabaseHas('blocked_ips', [
            'ip_address' => '192.168.1.100',
            'status' => BlockedIp::STATUS_BLOCKED,
        ]);
    }

    public function test_creates_security_alert_when_blocking(): void
    {
        $event = new RepeatedFailedLoginEvent(new AuditLog(), '192.168.1.101', 10);

        $this->listener->handle($event);

        $this->assertDatabaseHas('security_alerts', [
            'type' => 'auto_block_ip',
            'severity' => 'high',
            'status' => SecurityAlert::STATUS_OPEN,
        ]);
    }

    public function test_skips_if_already_blocked(): void
    {
        BlockedIp::create([
            'ip_address' => '192.168.1.102',
            'reason' => 'Pre-bloqueado',
            'status' => BlockedIp::STATUS_BLOCKED,
            'expires_at' => now()->addHour(),
            'created_at' => now(),
        ]);

        $event = new RepeatedFailedLoginEvent(new AuditLog(), '192.168.1.102', 10);

        $this->listener->handle($event);

        $this->assertDatabaseCount('blocked_ips', 1);
    }

    public function test_first_block_expires_in_20_minutes(): void
    {
        $event = new RepeatedFailedLoginEvent(new AuditLog(), '192.168.1.200', 5);

        $this->listener->handle($event);

        $record = BlockedIp::where('ip_address', '192.168.1.200')->first();

        $this->assertNotNull($record);
        $this->assertTrue($record->expires_at->lte(now()->addMinutes(21)));
        $this->assertTrue($record->expires_at->gte(now()->addMinutes(19)));
    }

    public function test_second_block_expires_in_30_minutes(): void
    {
        AuditLog::create([
            'event' => 'security.ip.blocked',
            'module' => 'security',
            'description' => 'Bloqueo anterior',
            'ip_address' => '192.168.1.201',
            'severity' => 'critical',
            'source' => 'system',
            'created_at' => now()->subDay(),
        ]);

        $event = new RepeatedFailedLoginEvent(new AuditLog(), '192.168.1.201', 8);

        $this->listener->handle($event);

        $record = BlockedIp::where('ip_address', '192.168.1.201')->first();

        $this->assertNotNull($record);
        $this->assertTrue($record->expires_at->lte(now()->addMinutes(31)));
        $this->assertTrue($record->expires_at->gte(now()->addMinutes(29)));
    }

    public function test_third_block_expires_in_1_hour(): void
    {
        foreach (range(1, 3) as $i) {
            AuditLog::create([
                'event' => 'security.ip.blocked',
                'module' => 'security',
                'description' => "Bloqueo anterior #{$i}",
                'ip_address' => '192.168.1.202',
                'severity' => 'critical',
                'source' => 'system',
                'created_at' => now()->subDay(),
            ]);
        }

        $event = new RepeatedFailedLoginEvent(new AuditLog(), '192.168.1.202', 15);

        $this->listener->handle($event);

        $record = BlockedIp::where('ip_address', '192.168.1.202')->first();

        $this->assertNotNull($record);
        $this->assertTrue($record->expires_at->lte(now()->addMinutes(61)));
        $this->assertTrue($record->expires_at->gte(now()->addMinutes(59)));
    }

    public function test_multiple_blocks_increase_expiry(): void
    {
        foreach (range(1, 3) as $i) {
            AuditLog::create([
                'event' => 'security.ip.blocked',
                'module' => 'security',
                'description' => "Bloqueo anterior #{$i}",
                'ip_address' => '10.0.0.88',
                'severity' => 'critical',
                'source' => 'system',
                'created_at' => now()->subDay(),
            ]);
        }

        $event = new RepeatedFailedLoginEvent(new AuditLog(), '10.0.0.88', 20);

        $this->listener->handle($event);

        $record = BlockedIp::where('ip_address', '10.0.0.88')->first();

        $this->assertNotNull($record);
        $this->assertNotNull($record->expires_at);
        $this->assertTrue($record->expires_at->isFuture());
        $this->assertTrue($record->expires_at->greaterThan(now()->addMinutes(30)));
    }
}
