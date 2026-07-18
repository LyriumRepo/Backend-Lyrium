<?php

declare(strict_types=1);

namespace Tests\Feature\Security;

use App\Models\BlockedIp;
use Tests\TestCase;
use Tests\Traits\SetUpSecurityTables;
use Tests\Traits\WithRoles;

final class SecurityAccessMiddlewareTest extends TestCase
{
    use SetUpSecurityTables, WithRoles;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpSecurityTables();
        $this->seedRoles();
    }

    public function test_allows_request_when_ip_not_blocked(): void
    {
        $user = $this->createSecurityAdmin();
        $this->actingAs($user);

        $response = $this->getJson('api/security/ips');

        $response->assertOk();
    }

    public function test_allows_request_when_ip_is_whitelisted(): void
    {
        $user = $this->createSecurityAdmin();
        $this->actingAs($user);

        BlockedIp::create([
            'ip_address' => '127.0.0.1',
            'reason' => 'Whitelisted',
            'status' => BlockedIp::STATUS_WHITELISTED,
            'created_at' => now(),
        ]);

        $response = $this->getJson('api/security/ips');

        $response->assertOk();
    }

    public function test_blocks_request_when_ip_is_blocked(): void
    {
        $user = $this->createSecurityAdmin();
        $this->actingAs($user);

        BlockedIp::create([
            'ip_address' => '127.0.0.1',
            'reason' => 'Test de bloqueo',
            'status' => BlockedIp::STATUS_BLOCKED,
            'expires_at' => now()->addHour(),
            'created_at' => now(),
        ]);

        $response = $this->getJson('api/security/ips');

        $response->assertForbidden()
            ->assertJsonPath('code', 'IP_BLOCKED');
    }

    public function test_allows_request_when_block_has_expired(): void
    {
        $user = $this->createSecurityAdmin();
        $this->actingAs($user);

        BlockedIp::create([
            'ip_address' => '127.0.0.1',
            'reason' => 'Expired block',
            'status' => BlockedIp::STATUS_BLOCKED,
            'expires_at' => now()->subMinute(),
            'created_at' => now()->subDay(),
        ]);

        $response = $this->getJson('api/security/ips');

        $response->assertOk();
    }

    public function test_allows_request_when_ip_is_flagged(): void
    {
        $user = $this->createSecurityAdmin();
        $this->actingAs($user);

        BlockedIp::create([
            'ip_address' => '127.0.0.1',
            'reason' => 'Flagged',
            'status' => BlockedIp::STATUS_FLAGGED,
            'created_at' => now(),
        ]);

        $response = $this->getJson('api/security/ips');

        $response->assertOk();
    }
}
