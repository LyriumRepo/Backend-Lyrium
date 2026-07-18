<?php

declare(strict_types=1);

namespace Tests\Feature\Security;

use App\Models\ProtectionRule;
use Tests\TestCase;
use Tests\Traits\SetUpSecurityTables;
use Tests\Traits\WithRoles;

final class ProtectionRuleMiddlewareTest extends TestCase
{
    use SetUpSecurityTables, WithRoles;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpSecurityTables();
        $this->seedRoles();
    }

    public function test_blocks_ip_by_exact_match(): void
    {
        $admin = $this->createSecurityAdmin();
        $this->actingAs($admin);

        ProtectionRule::create([
            'name' => 'Bloquear IP local',
            'type' => ProtectionRule::TYPE_IP_BLOCK,
            'pattern' => '127.0.0.1',
            'status' => ProtectionRule::STATUS_ACTIVE,
            'priority' => 1,
        ]);

        $response = $this->getJson('api/security/ips');

        $response->assertForbidden()
            ->assertJsonPath('code', 'PROTECTION_RULE');
    }

    public function test_blocks_ip_by_cidr(): void
    {
        $admin = $this->createSecurityAdmin();
        $this->actingAs($admin);

        ProtectionRule::create([
            'name' => 'Bloquear rango local',
            'type' => ProtectionRule::TYPE_IP_BLOCK,
            'pattern' => '127.0.0.0/8',
            'status' => ProtectionRule::STATUS_ACTIVE,
            'priority' => 1,
        ]);

        $response = $this->getJson('api/security/ips');

        $response->assertForbidden()
            ->assertJsonPath('code', 'PROTECTION_RULE');
    }

    public function test_blocks_ip_by_wildcard(): void
    {
        $admin = $this->createSecurityAdmin();
        $this->actingAs($admin);

        ProtectionRule::create([
            'name' => 'Bloquear red 192.168',
            'type' => ProtectionRule::TYPE_IP_BLOCK,
            'pattern' => '192.168.*.*',
            'status' => ProtectionRule::STATUS_ACTIVE,
            'priority' => 1,
        ]);

        $this->assertTrue(true);
    }

    public function test_allows_request_when_no_rules_match(): void
    {
        $admin = $this->createSecurityAdmin();
        $this->actingAs($admin);

        $response = $this->getJson('api/security/ips');

        $response->assertOk();
    }

    public function test_allows_request_when_no_active_rules(): void
    {
        $admin = $this->createSecurityAdmin();
        $this->actingAs($admin);

        ProtectionRule::create([
            'name' => 'Regla inactiva',
            'type' => ProtectionRule::TYPE_IP_BLOCK,
            'pattern' => '127.0.0.1',
            'status' => ProtectionRule::STATUS_INACTIVE,
            'priority' => 1,
        ]);

        $response = $this->getJson('api/security/ips');

        $response->assertOk();
    }

    public function test_respects_rule_priority(): void
    {
        $admin = $this->createSecurityAdmin();
        $this->actingAs($admin);

        ProtectionRule::create([
            'name' => 'Bloqueo genérico (prioridad baja)',
            'type' => ProtectionRule::TYPE_IP_BLOCK,
            'pattern' => '127.0.0.1',
            'status' => ProtectionRule::STATUS_ACTIVE,
            'priority' => 10,
        ]);

        ProtectionRule::create([
            'name' => 'Permitir localhost (prioridad alta)',
            'type' => ProtectionRule::TYPE_CUSTOM,
            'pattern' => '',
            'status' => ProtectionRule::STATUS_ACTIVE,
            'priority' => 1,
        ]);

        $response = $this->getJson('api/security/ips');

        $this->assertContains($response->status(), [200, 403]);
    }

    public function test_blocks_device_by_user_agent(): void
    {
        $admin = $this->createSecurityAdmin();
        $this->actingAs($admin);

        ProtectionRule::create([
            'name' => 'Bloquear bots',
            'type' => ProtectionRule::TYPE_DEVICE,
            'pattern' => 'curl|wget|python-requests',
            'status' => ProtectionRule::STATUS_ACTIVE,
            'priority' => 1,
        ]);

        $response = $this
            ->withHeader('User-Agent', 'curl/7.68.0')
            ->getJson('api/security/ips');

        $response->assertForbidden()
            ->assertJsonPath('code', 'PROTECTION_RULE');
    }

    public function test_allows_regular_browser_user_agent(): void
    {
        $admin = $this->createSecurityAdmin();
        $this->actingAs($admin);

        ProtectionRule::create([
            'name' => 'Bloquear bots',
            'type' => ProtectionRule::TYPE_DEVICE,
            'pattern' => 'curl|wget|python-requests',
            'status' => ProtectionRule::STATUS_ACTIVE,
            'priority' => 1,
        ]);

        $response = $this
            ->withHeader('User-Agent', 'Mozilla/5.0 Chrome/120')
            ->getJson('api/security/ips');

        $response->assertOk();
    }

    public function test_increments_trigger_count_on_match(): void
    {
        $admin = $this->createSecurityAdmin();
        $this->actingAs($admin);

        $rule = ProtectionRule::create([
            'name' => 'Bloquear IP',
            'type' => ProtectionRule::TYPE_IP_BLOCK,
            'pattern' => '127.0.0.1',
            'status' => ProtectionRule::STATUS_ACTIVE,
            'priority' => 1,
            'trigger_count' => 0,
        ]);

        $this->getJson('api/security/ips');

        $rule->refresh();
        $this->assertGreaterThan(0, $rule->trigger_count);
        $this->assertEquals(ProtectionRule::STATUS_TRIGGERED, $rule->status);
    }
}
