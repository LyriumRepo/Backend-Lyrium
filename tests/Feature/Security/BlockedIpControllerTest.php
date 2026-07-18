<?php

declare(strict_types=1);

namespace Tests\Feature\Security;

use App\Models\BlockedIp;
use Tests\TestCase;
use Tests\Traits\SetUpSecurityTables;
use Tests\Traits\WithRoles;

final class BlockedIpControllerTest extends TestCase
{
    use SetUpSecurityTables, WithRoles;

    private const BASE_URL = 'api/security/ips';

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpSecurityTables();
        $this->seedRoles();
    }

    public function test_index_requires_authentication(): void
    {
        $response = $this->getJson(self::BASE_URL);

        $response->assertUnauthorized();
    }

    public function test_index_requires_security_admin_role(): void
    {
        $user = $this->createAdmin();
        $this->actingAs($user);

        $response = $this->getJson(self::BASE_URL);

        $response->assertForbidden();
    }

    public function test_index_returns_paginated_ips(): void
    {
        $admin = $this->createSecurityAdmin();
        $this->actingAs($admin);

        foreach (range(1, 3) as $i) {
            BlockedIp::create([
                'ip_address' => "10.0.0.{$i}",
                'reason' => "Test IP {$i}",
                'status' => BlockedIp::STATUS_BLOCKED,
                'created_at' => now(),
            ]);
        }

        $response = $this->getJson(self::BASE_URL);

        $response->assertOk()
            ->assertJsonStructure([
                'data' => ['items', 'pagination' => ['current_page', 'last_page', 'per_page', 'total']],
            ]);
    }

    public function test_index_filters_by_status(): void
    {
        $admin = $this->createSecurityAdmin();
        $this->actingAs($admin);

        BlockedIp::create([
            'ip_address' => '192.168.1.1',
            'reason' => 'Test blocked',
            'status' => BlockedIp::STATUS_BLOCKED,
            'created_at' => now(),
        ]);
        BlockedIp::create([
            'ip_address' => '192.168.1.2',
            'reason' => 'Test flagged',
            'status' => BlockedIp::STATUS_FLAGGED,
            'created_at' => now(),
        ]);

        $response = $this->getJson(self::BASE_URL . '?status=' . BlockedIp::STATUS_BLOCKED);

        $response->assertOk();
        $this->assertCount(1, $response->json('data.items'));
    }

    public function test_store_creates_blocked_ip(): void
    {
        $admin = $this->createSecurityAdmin();
        $this->actingAs($admin);

        $response = $this->postJson(self::BASE_URL, [
            'ip_address' => '10.0.0.1',
            'reason' => 'Prueba de bloqueo',
            'status' => BlockedIp::STATUS_BLOCKED,
        ]);

        $response->assertCreated()
            ->assertJsonPath('success', true);

        $this->assertDatabaseHas('blocked_ips', [
            'ip_address' => '10.0.0.1',
            'status' => BlockedIp::STATUS_BLOCKED,
        ]);
    }

    public function test_store_fails_with_duplicate_ip(): void
    {
        $admin = $this->createSecurityAdmin();
        $this->actingAs($admin);

        BlockedIp::create([
            'ip_address' => '10.0.0.1',
            'reason' => 'Previous',
            'status' => BlockedIp::STATUS_BLOCKED,
            'created_at' => now(),
        ]);

        $response = $this->postJson(self::BASE_URL, [
            'ip_address' => '10.0.0.1',
            'reason' => 'Intento duplicado',
            'status' => BlockedIp::STATUS_BLOCKED,
        ]);

        $response->assertStatus(422);
    }

    public function test_store_creates_whitelisted_ip(): void
    {
        $admin = $this->createSecurityAdmin();
        $this->actingAs($admin);

        $response = $this->postJson(self::BASE_URL, [
            'ip_address' => '192.168.1.100',
            'reason' => 'IP confiable',
            'status' => BlockedIp::STATUS_WHITELISTED,
        ]);

        $response->assertCreated();

        $this->assertDatabaseHas('blocked_ips', [
            'ip_address' => '192.168.1.100',
            'status' => BlockedIp::STATUS_WHITELISTED,
        ]);
    }

    public function test_show_returns_blocked_ip(): void
    {
        $admin = $this->createSecurityAdmin();
        $this->actingAs($admin);

        $record = BlockedIp::create([
            'ip_address' => '10.0.0.50',
            'reason' => 'Test show',
            'status' => BlockedIp::STATUS_BLOCKED,
            'created_at' => now(),
        ]);

        $response = $this->getJson(self::BASE_URL . "/{$record->id}");

        $response->assertOk()
            ->assertJsonPath('data.ip_address', '10.0.0.50');
    }

    public function test_update_unblocks_ip(): void
    {
        $admin = $this->createSecurityAdmin();
        $this->actingAs($admin);

        $record = BlockedIp::create([
            'ip_address' => '10.0.0.99',
            'reason' => 'Test update',
            'status' => BlockedIp::STATUS_BLOCKED,
            'created_at' => now(),
        ]);

        $response = $this->putJson(self::BASE_URL . "/{$record->id}", [
            'status' => BlockedIp::STATUS_UNBLOCKED,
        ]);

        $response->assertOk();

        $this->assertDatabaseHas('blocked_ips', [
            'id' => $record->id,
            'status' => BlockedIp::STATUS_UNBLOCKED,
        ]);
    }

    public function test_destroy_deletes_ip_record(): void
    {
        $admin = $this->createSecurityAdmin();
        $this->actingAs($admin);

        $record = BlockedIp::create([
            'ip_address' => '10.0.0.77',
            'reason' => 'Test destroy',
            'status' => BlockedIp::STATUS_BLOCKED,
            'created_at' => now(),
        ]);

        $response = $this->deleteJson(self::BASE_URL . "/{$record->id}");

        $response->assertOk();

        $this->assertDatabaseMissing('blocked_ips', ['id' => $record->id]);
    }
}
