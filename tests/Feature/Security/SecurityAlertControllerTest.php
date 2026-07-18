<?php

declare(strict_types=1);

namespace Tests\Feature\Security;

use App\Models\SecurityAlert;
use Tests\TestCase;
use Tests\Traits\SetUpSecurityTables;
use Tests\Traits\WithRoles;

final class SecurityAlertControllerTest extends TestCase
{
    use SetUpSecurityTables, WithRoles;

    private const BASE_URL = 'api/security/alerts';

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

    public function test_index_returns_paginated_alerts(): void
    {
        $admin = $this->createSecurityAdmin();
        $this->actingAs($admin);

        SecurityAlert::create([
            'type' => 'test',
            'severity' => 'high',
            'title' => 'Alerta de prueba',
            'message' => 'Mensaje de prueba',
            'status' => SecurityAlert::STATUS_OPEN,
            'created_at' => now(),
        ]);

        $response = $this->getJson(self::BASE_URL);

        $response->assertOk()
            ->assertJsonStructure([
                'data' => ['items', 'active_count', 'pagination'],
            ]);
    }

    public function test_show_returns_alert(): void
    {
        $admin = $this->createSecurityAdmin();
        $this->actingAs($admin);

        $alert = SecurityAlert::create([
            'type' => 'test',
            'severity' => 'high',
            'title' => 'Alerta individual',
            'message' => 'Detalle de alerta',
            'status' => SecurityAlert::STATUS_OPEN,
            'created_at' => now(),
        ]);

        $response = $this->getJson(self::BASE_URL . "/{$alert->id}");

        $response->assertOk()
            ->assertJsonPath('data.title', 'Alerta individual');
    }

    public function test_dismiss_changes_status_to_dismissed(): void
    {
        $admin = $this->createSecurityAdmin();
        $this->actingAs($admin);

        $alert = SecurityAlert::create([
            'type' => 'test',
            'severity' => 'medium',
            'title' => 'Alerta a descartar',
            'message' => 'Se descartará',
            'status' => SecurityAlert::STATUS_OPEN,
            'created_at' => now(),
        ]);

        $response = $this->putJson(self::BASE_URL . "/{$alert->id}/dismiss");

        $response->assertOk();

        $this->assertDatabaseHas('security_alerts', [
            'id' => $alert->id,
            'status' => SecurityAlert::STATUS_DISMISSED,
        ]);
    }

    public function test_resolve_changes_status_to_resolved(): void
    {
        $admin = $this->createSecurityAdmin();
        $this->actingAs($admin);

        $alert = SecurityAlert::create([
            'type' => 'test',
            'severity' => 'critical',
            'title' => 'Alerta a resolver',
            'message' => 'Se resolverá',
            'status' => SecurityAlert::STATUS_OPEN,
            'created_at' => now(),
        ]);

        $response = $this->putJson(self::BASE_URL . "/{$alert->id}/resolve");

        $response->assertOk();

        $this->assertDatabaseHas('security_alerts', [
            'id' => $alert->id,
            'status' => SecurityAlert::STATUS_RESOLVED,
            'resolved_by' => $admin->id,
        ]);
    }

    public function test_cannot_dismiss_already_resolved_alert(): void
    {
        $admin = $this->createSecurityAdmin();
        $this->actingAs($admin);

        $alert = SecurityAlert::create([
            'type' => 'test',
            'severity' => 'low',
            'title' => 'Ya resuelta',
            'message' => 'No se puede descartar',
            'status' => SecurityAlert::STATUS_RESOLVED,
            'resolved_at' => now(),
            'resolved_by' => $admin->id,
            'created_at' => now()->subHour(),
        ]);

        $response = $this->putJson(self::BASE_URL . "/{$alert->id}/dismiss");

        $response->assertNotFound();
    }
}
