<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Tests\Traits\WithRoles;

final class UserManagementTest extends TestCase
{
    use RefreshDatabase, WithRoles;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedRoles();
    }

    public function test_admin_can_assign_a_new_role_to_a_user(): void
    {
        $admin = $this->createAdmin();
        $user = $this->createCustomer();

        $response = $this->actingAs($admin)
            ->putJson("/api/users/{$user->id}/role", [
                'role' => 'logistics_operator',
            ]);

        $response->assertOk()
            ->assertJsonPath('role', 'logistics_operator');

        $this->assertTrue($user->fresh()->hasRole('logistics_operator'));
    }

    public function test_admin_can_toggle_user_ban_state(): void
    {
        $admin = $this->createAdmin();
        $user = $this->createCustomer();

        $firstResponse = $this->actingAs($admin)
            ->putJson("/api/users/{$user->id}/ban");

        $firstResponse->assertOk()
            ->assertJsonPath('is_banned', true);

        $this->assertTrue((bool) $user->fresh()->is_banned);

        $secondResponse = $this->actingAs($admin)
            ->putJson("/api/users/{$user->id}/ban");

        $secondResponse->assertOk()
            ->assertJsonPath('is_banned', false);
    }

    public function test_user_listing_returns_admin_module_fields_and_status_filter(): void
    {
        $admin = $this->createAdmin();
        $seller = $this->createSeller();
        $bannedCustomer = User::factory()->create([
            'email_verified_at' => null,
            'is_banned' => true,
        ]);
        $bannedCustomer->assignRole('customer');

        $response = $this->actingAs($admin)
            ->getJson('/api/users?role=seller&status=active');

        $response->assertOk()
            ->assertJsonPath('pagination.total', 1)
            ->assertJsonPath('data.0.id', $seller->id)
            ->assertJsonPath('data.0.role', 'seller')
            ->assertJsonPath('data.0.is_banned', false)
            ->assertJsonPath('data.0.email_verified', true)
            ->assertJsonPath('data.0.stores_count', 1);

        $bannedResponse = $this->actingAs($admin)
            ->getJson('/api/users?status=banned');

        $bannedResponse->assertOk()
            ->assertJsonPath('pagination.total', 1)
            ->assertJsonPath('data.0.id', $bannedCustomer->id)
            ->assertJsonPath('data.0.is_banned', true);
    }
}
