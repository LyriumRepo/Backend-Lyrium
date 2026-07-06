<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Contract;
use App\Models\Product;
use App\Models\Service;
use App\Notifications\ContractStatusNotification;
use App\Notifications\ProductStatusNotification;
use App\Notifications\ServiceStatusNotification;
use App\Notifications\StoreStatusNotification;
use App\Notifications\UserBannedNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;
use Tests\Traits\WithRoles;

final class NotificationTest extends TestCase
{
    use RefreshDatabase, WithRoles;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedRoles();
    }

    // ─── FASE 1: Seller Ban ─────────────────────────────────────────

    public function test_admin_suspends_store_and_notifies_seller(): void
    {
        Notification::fake();
        $admin = $this->createAdmin();
        $seller = $this->createSeller();
        $store = $seller->ownedStores()->first();

        $response = $this->actingAs($admin)->putJson(
            "/api/admin/sellers/{$store->id}/store-status",
            ['status' => 'suspended', 'reason' => 'Incumplimiento de términos']
        );

        $response->assertOk();
        $this->assertDatabaseHas('stores', [
            'id' => $store->id,
            'status' => 'suspended',
        ]);
        Notification::assertSentTo($seller, StoreStatusNotification::class);
    }

    public function test_admin_bans_user_and_notifies_seller(): void
    {
        Notification::fake();
        $admin = $this->createAdmin();
        $seller = $this->createSeller();

        $response = $this->actingAs($admin)->putJson(
            "/api/admin/sellers/{$seller->id}/ban",
            ['reason' => 'Fraude detectado']
        );

        $response->assertOk();
        $this->assertDatabaseHas('users', [
            'id' => $seller->id,
            'is_banned' => true,
        ]);
        Notification::assertSentTo($seller, UserBannedNotification::class);
    }

    // ─── FASE 2: Products ───────────────────────────────────────────

    public function test_seller_creates_product_notifies_admins(): void
    {
        Notification::fake();
        $admin = $this->createAdmin();
        $seller = $this->createSellerWithContract();
        $store = $seller->ownedStores()->first();

        $response = $this->actingAs($seller)->postJson('/api/products', [
            'type' => 'physical',
            'name' => 'Aceite de Coco Orgánico',
            'description' => 'Aceite prensado en frío',
            'price' => 35.00,
            'stock' => 50,
        ]);

        $response->assertCreated()
            ->assertJsonPath('status', 'pending_review');

        Notification::assertSentTo($admin, ProductStatusNotification::class);
    }

    public function test_admin_approves_product_notifies_seller(): void
    {
        Notification::fake();
        $admin = $this->createAdmin();
        $seller = $this->createSellerWithContract();
        $store = $seller->ownedStores()->first();
        $product = Product::factory()->pendingReview()->create(['store_id' => $store->id]);

        $response = $this->actingAs($admin)->putJson(
            "/api/products/{$product->id}/status",
            ['status' => 'approved']
        );

        $response->assertOk();
        $this->assertDatabaseHas('products', [
            'id' => $product->id,
            'status' => 'approved',
        ]);
        Notification::assertSentTo($seller, ProductStatusNotification::class);
    }

    public function test_admin_rejects_product_requires_reason(): void
    {
        Notification::fake();
        $admin = $this->createAdmin();
        $seller = $this->createSellerWithContract();
        $store = $seller->ownedStores()->first();
        $product = Product::factory()->pendingReview()->create(['store_id' => $store->id]);

        $response = $this->actingAs($admin)->putJson(
            "/api/products/{$product->id}/status",
            ['status' => 'rejected']
        );

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['reason']);

        Notification::assertNothingSent();
    }

    public function test_admin_rejects_product_with_reason_notifies_seller(): void
    {
        Notification::fake();
        $admin = $this->createAdmin();
        $seller = $this->createSellerWithContract();
        $store = $seller->ownedStores()->first();
        $product = Product::factory()->pendingReview()->create(['store_id' => $store->id]);

        $response = $this->actingAs($admin)->putJson(
            "/api/products/{$product->id}/status",
            ['status' => 'rejected', 'reason' => 'Falta certificación orgánica']
        );

        $response->assertOk();
        Notification::assertSentTo($seller, ProductStatusNotification::class);
    }

    // ─── FASE 3: Contracts ──────────────────────────────────────────

    public function test_admin_creates_contract_notifies_admins(): void
    {
        Notification::fake();
        $admin = $this->createAdmin();
        $seller = $this->createSeller();
        $store = $seller->ownedStores()->first();

        $response = $this->actingAs($admin)->postJson('/api/contracts', [
            'company' => 'BioPerú S.A.C.',
            'modality' => 'VIRTUAL',
            'start' => now()->toDateString(),
            'storeId' => $store->id,
        ]);

        $response->assertCreated();
        Notification::assertSentTo($admin, ContractStatusNotification::class);
    }

    public function test_admin_activates_contract_notifies_seller(): void
    {
        Notification::fake();
        $admin = $this->createAdmin();
        $seller = $this->createSeller();
        $store = $seller->ownedStores()->first();
        $contract = Contract::factory()->create([
            'store_id' => $store->id,
            'status' => 'PENDING',
        ]);

        $response = $this->actingAs($admin)->putJson(
            "/api/contracts/{$contract->id}/status",
            ['status' => 'ACTIVE']
        );

        $response->assertOk();
        Notification::assertSentTo($seller, ContractStatusNotification::class);
    }

    public function test_seller_renews_contract_notifies_both(): void
    {
        Notification::fake();
        $admin = $this->createAdmin();
        $seller = $this->createSeller();
        $store = $seller->ownedStores()->first();
        $contract = Contract::factory()->active()->create([
            'store_id' => $store->id,
            'version' => 1,
        ]);

        $response = $this->actingAs($seller)->postJson(
            "/api/contracts/{$contract->id}/renew"
        );

        $response->assertCreated();
        $this->assertDatabaseHas('contracts', [
            'parent_contract_id' => $contract->id,
            'version' => 2,
            'status' => 'ACTIVE',
        ]);
        Notification::assertSentTo($seller, ContractStatusNotification::class);
        Notification::assertSentTo($admin, ContractStatusNotification::class);
    }

    // ─── FASE 4: Services ───────────────────────────────────────────

    public function test_seller_creates_service_notifies_admins(): void
    {
        Notification::fake();
        $admin = $this->createAdmin();
        $seller = $this->createSellerWithContract();
        $store = $seller->ownedStores()->first();

        $response = $this->actingAs($seller)->postJson('/api/services', [
            'name' => 'Masaje de Aromaterapia',
            'description' => 'Masaje relajante con aceites esenciales',
            'price' => 120.00,
            'duration_minutes' => 60,
        ]);

        $response->assertCreated();
        $serviceId = $response->json('id');
        $this->assertDatabaseHas('services', [
            'id' => $serviceId,
            'status' => 'pending_review',
        ]);
        Notification::assertSentTo($admin, ServiceStatusNotification::class);
    }

    public function test_admin_approves_service_notifies_seller(): void
    {
        Notification::fake();
        $admin = $this->createAdmin();
        $seller = $this->createSellerWithContract();
        $store = $seller->ownedStores()->first();
        $service = Service::create([
            'store_id' => $store->id,
            'name' => 'Yoga Terapéutico',
            'slug' => 'yoga-terapeutico',
            'price' => 80.00,
            'duration_minutes' => 45,
            'status' => Service::STATUS_PENDING_REVIEW,
        ]);

        $response = $this->actingAs($admin)->putJson(
            "/api/services/{$service->id}/status",
            ['status' => 'approved']
        );

        $response->assertOk();
        $this->assertDatabaseHas('services', [
            'id' => $service->id,
            'status' => 'approved',
        ]);
        Notification::assertSentTo($seller, ServiceStatusNotification::class);
    }

    public function test_admin_rejects_service_requires_reason(): void
    {
        Notification::fake();
        $admin = $this->createAdmin();
        $seller = $this->createSellerWithContract();
        $store = $seller->ownedStores()->first();
        $service = Service::create([
            'store_id' => $store->id,
            'name' => 'Clase de Pilates',
            'slug' => 'clase-pilates',
            'price' => 60.00,
            'duration_minutes' => 50,
            'status' => Service::STATUS_PENDING_REVIEW,
        ]);

        $response = $this->actingAs($admin)->putJson(
            "/api/services/{$service->id}/status",
            ['status' => 'rejected']
        );

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['reason']);

        Notification::assertNothingSent();
    }

    public function test_admin_rejects_service_with_reason_notifies_seller(): void
    {
        Notification::fake();
        $admin = $this->createAdmin();
        $seller = $this->createSellerWithContract();
        $store = $seller->ownedStores()->first();
        $service = Service::create([
            'store_id' => $store->id,
            'name' => 'Consulta Nutricional',
            'slug' => 'consulta-nutricional',
            'price' => 150.00,
            'duration_minutes' => 90,
            'status' => Service::STATUS_PENDING_REVIEW,
        ]);

        $response = $this->actingAs($admin)->putJson(
            "/api/services/{$service->id}/status",
            ['status' => 'rejected', 'reason' => 'No cumple con los requisitos sanitarios']
        );

        $response->assertOk();
        Notification::assertSentTo($seller, ServiceStatusNotification::class);
    }
}
