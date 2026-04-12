<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Store;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Tests\Traits\WithRoles;

final class StoreTest extends TestCase
{
    use RefreshDatabase, WithRoles;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedRoles();
    }

    // ─── INDEX (ADMIN) ──────────────────────────────────────────────

    public function test_admin_can_list_stores(): void
    {
        $admin = $this->createAdmin();
        Store::factory()->count(3)->create();

        $response = $this->actingAs($admin)
            ->getJson('/api/stores');

        $response->assertOk()
            ->assertJsonStructure(['data', 'pagination']);
    }

    public function test_admin_can_filter_stores_by_status(): void
    {
        $admin = $this->createAdmin();
        Store::factory()->approved()->count(2)->create();
        Store::factory()->create(); // pending

        $response = $this->actingAs($admin)
            ->getJson('/api/stores?status=approved');

        $response->assertOk();
        $this->assertCount(2, $response->json('data'));
    }

    public function test_admin_can_search_stores(): void
    {
        $admin = $this->createAdmin();
        Store::factory()->create(['trade_name' => 'BioTienda Especial']);
        Store::factory()->create(['trade_name' => 'Otra Tienda']);

        $response = $this->actingAs($admin)
            ->getJson('/api/stores?search=BioTienda');

        $response->assertOk();
        $this->assertCount(1, $response->json('data'));
    }

    public function test_seller_cannot_list_all_stores(): void
    {
        $seller = $this->createSeller();

        $response = $this->actingAs($seller)
            ->getJson('/api/stores');

        $response->assertForbidden();
    }

    // ─── SHOW (ADMIN) ───────────────────────────────────────────────

    public function test_admin_can_view_store(): void
    {
        $admin = $this->createAdmin();
        $store = Store::factory()->create();

        $response = $this->actingAs($admin)
            ->getJson("/api/stores/{$store->id}");

        $response->assertOk()
            ->assertJsonPath('id', (string) $store->id)
            ->assertJsonPath('profile_complete', false)
            ->assertJsonPath('missing_profile_fields.0', 'Razon social');
    }

    // ─── STORE (SELLER) ─────────────────────────────────────────────

    public function test_seller_can_create_store(): void
    {
        $seller = $this->createSeller();

        $response = $this->actingAs($seller)
            ->postJson('/api/stores', [
                'trade_name' => 'Mi Nueva Tienda',
                'ruc' => '20999888777',
                'corporate_email' => 'tienda@test.com',
            ]);

        $response->assertCreated();
        $this->assertDatabaseHas('stores', ['ruc' => '20999888777']);
    }

    public function test_create_store_validates_ruc_uniqueness(): void
    {
        $seller = $this->createSeller();
        Store::factory()->create(['ruc' => '20123456789']);

        $response = $this->actingAs($seller)
            ->postJson('/api/stores', [
                'trade_name' => 'Another Store',
                'ruc' => '20123456789',
                'corporate_email' => 'store@test.com',
            ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['ruc']);
    }

    // ─── UPDATE (SELLER) ────────────────────────────────────────────

    public function test_seller_can_update_store(): void
    {
        $seller = $this->createSeller();
        $store = $seller->ownedStores()->first();

        $response = $this->actingAs($seller)
            ->putJson("/api/stores/{$store->id}", [
                'trade_name' => 'Updated Name',
            ]);

        $response->assertOk();
        $this->assertDatabaseHas('stores', [
            'id' => $store->id,
            'trade_name' => 'Updated Name',
        ]);
    }

    // ─── UPDATE STATUS (ADMIN) ──────────────────────────────────────

    public function test_admin_can_approve_store(): void
    {
        $admin = $this->createAdmin();
        $store = Store::factory()->withCompleteProfile()->create(['status' => 'pending']);

        $response = $this->actingAs($admin)
            ->putJson("/api/stores/{$store->id}/status", [
                'status' => 'approved',
            ]);

        $response->assertOk();
        $store->refresh();
        $this->assertEquals('approved', $store->status);
        $this->assertNotNull($store->approved_at);
    }

    public function test_admin_cannot_approve_store_with_incomplete_profile(): void
    {
        $admin = $this->createAdmin();
        $store = Store::factory()->create([
            'status' => 'pending',
            'razon_social' => null,
            'rep_legal_nombre' => null,
            'rep_legal_dni' => null,
            'direccion_fiscal' => null,
        ]);

        $response = $this->actingAs($admin)
            ->putJson("/api/stores/{$store->id}/status", [
                'status' => 'approved',
            ]);

        $response->assertUnprocessable()
            ->assertJsonPath('message', 'No se puede aprobar la tienda: el perfil esta incompleto.')
            ->assertJsonCount(4, 'missing_fields')
            ->assertJsonPath('missing_fields.0', 'Razon social');

        $this->assertDatabaseHas('stores', [
            'id' => $store->id,
            'status' => 'pending',
            'approved_at' => null,
        ]);
    }

    public function test_admin_can_reject_store(): void
    {
        $admin = $this->createAdmin();
        $store = Store::factory()->create(['status' => 'pending']);

        $response = $this->actingAs($admin)
            ->putJson("/api/stores/{$store->id}/status", [
                'status' => 'rejected',
                'reason' => 'Documentos incompletos',
            ]);

        $response->assertOk();
        $this->assertDatabaseHas('stores', [
            'id' => $store->id,
            'status' => 'rejected',
        ]);
    }

    public function test_admin_can_ban_store(): void
    {
        $admin = $this->createAdmin();
        $store = Store::factory()->approved()->create();

        $response = $this->actingAs($admin)
            ->putJson("/api/stores/{$store->id}/status", [
                'status' => 'banned',
            ]);

        $response->assertOk();
        $store->refresh();
        $this->assertEquals('banned', $store->status);
        $this->assertNotNull($store->banned_at);
    }

    public function test_seller_cannot_change_store_status(): void
    {
        $seller = $this->createSeller();
        $store = Store::factory()->create();

        $response = $this->actingAs($seller)
            ->putJson("/api/stores/{$store->id}/status", [
                'status' => 'approved',
            ]);

        $response->assertForbidden();
    }

    public function test_update_status_validates_allowed_values(): void
    {
        $admin = $this->createAdmin();
        $store = Store::factory()->create();

        $response = $this->actingAs($admin)
            ->putJson("/api/stores/{$store->id}/status", [
                'status' => 'invalid_status',
            ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['status']);
    }
}
