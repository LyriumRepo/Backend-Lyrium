<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Product;
use App\Models\Store;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Tests\Traits\WithRoles;

final class ProductTest extends TestCase
{
    use RefreshDatabase, WithRoles;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedRoles();
    }

    // ─── INDEX (PUBLIC) ─────────────────────────────────────────────

    public function test_public_sees_only_approved_products(): void
    {
        $store = Store::factory()->approved()->create();
        Product::factory()->approved()->count(2)->create(['store_id' => $store->id]);
        Product::factory()->pendingReview()->create(['store_id' => $store->id]);

        $response = $this->getJson('/api/products');

        $response->assertOk();
        $this->assertCount(2, $response->json('data'));
    }

    public function test_admin_sees_all_products(): void
    {
        $admin = $this->createAdmin();
        $store = Store::factory()->approved()->create();
        Product::factory()->approved()->count(2)->create(['store_id' => $store->id]);
        Product::factory()->pendingReview()->create(['store_id' => $store->id]);

        $response = $this->actingAs($admin)
            ->getJson('/api/products');

        $response->assertOk();
        $this->assertCount(3, $response->json('data'));
    }

    public function test_seller_sees_only_own_products(): void
    {
        $seller = $this->createSeller();
        $store = $seller->ownedStores()->first();
        Product::factory()->count(2)->create(['store_id' => $store->id]);

        // Other seller's products
        $otherStore = Store::factory()->approved()->create();
        Product::factory()->approved()->count(3)->create(['store_id' => $otherStore->id]);

        $response = $this->actingAs($seller)
            ->getJson('/api/products');

        $response->assertOk();
        $this->assertCount(2, $response->json('data'));
    }

    public function test_filter_products_by_search(): void
    {
        $store = Store::factory()->approved()->create();
        Product::factory()->approved()->create([
            'store_id' => $store->id,
            'name' => 'Quinua Orgánica Premium',
        ]);
        Product::factory()->approved()->create([
            'store_id' => $store->id,
            'name' => 'Fertilizante Natural',
        ]);

        $response = $this->getJson('/api/products?search=Quinua');

        $response->assertOk();
        $this->assertCount(1, $response->json('data'));
    }

    public function test_filter_products_by_type(): void
    {
        $store = Store::factory()->approved()->create();
        Product::factory()->physical()->approved()->count(2)->create(['store_id' => $store->id]);
        Product::factory()->digital()->approved()->create(['store_id' => $store->id]);

        $response = $this->getJson('/api/products?type=digital');

        $response->assertOk();
        $this->assertCount(1, $response->json('data'));
    }

    public function test_filter_products_by_category(): void
    {
        $store = Store::factory()->approved()->create();
        $category = Category::factory()->create(['slug' => 'semillas']);

        $product = Product::factory()->approved()->create(['store_id' => $store->id]);
        $product->categories()->attach($category);

        Product::factory()->approved()->create(['store_id' => $store->id]);

        $response = $this->getJson('/api/products?category=semillas');

        $response->assertOk();
        $this->assertCount(1, $response->json('data'));
    }

    public function test_filter_products_in_stock(): void
    {
        $store = Store::factory()->approved()->create();
        Product::factory()->approved()->create(['store_id' => $store->id, 'stock' => 10]);
        Product::factory()->approved()->create(['store_id' => $store->id, 'stock' => 0]);

        $response = $this->getJson('/api/products?inStock=true');

        $response->assertOk();
        $this->assertCount(1, $response->json('data'));
    }

    // ─── SHOW (PUBLIC) ──────────────────────────────────────────────

    public function test_show_product(): void
    {
        $store = Store::factory()->approved()->create();
        $product = Product::factory()->physical()->create(['store_id' => $store->id]);

        $response = $this->getJson("/api/products/{$product->id}");

        $response->assertOk()
            ->assertJsonPath('id', (string) $product->id)
            ->assertJsonPath('type', 'physical');
    }

    public function test_show_nonexistent_product_returns_404(): void
    {
        $response = $this->getJson('/api/products/99999');

        $response->assertNotFound();
    }

    // ─── STORE (SELLER) ─────────────────────────────────────────────

    public function test_seller_can_create_physical_product(): void
    {
        $seller = $this->createSellerWithContract();

        $response = $this->actingAs($seller)
            ->postJson('/api/products', [
                'type' => 'physical',
                'name' => 'Semillas de Quinua',
                'description' => 'Semillas orgánicas certificadas',
                'price' => 25.50,
                'stock' => 100,
                'weight' => 0.5,
                'dimensions' => '10x15x5',
            ]);

        $response->assertCreated()
            ->assertJsonPath('name', 'Semillas de Quinua')
            ->assertJsonPath('type', 'physical')
            ->assertJsonPath('status', 'pending_review');
    }

    public function test_seller_can_create_digital_product(): void
    {
        $seller = $this->createSellerWithContract();

        $response = $this->actingAs($seller)
            ->postJson('/api/products', [
                'type' => 'digital',
                'name' => 'Guía de Cultivo PDF',
                'description' => 'Guía completa',
                'price' => 15.00,
                'downloadUrl' => 'https://example.com/guide.pdf',
                'downloadLimit' => 3,
                'fileType' => 'pdf',
                'fileSize' => 2048,
            ]);

        $response->assertCreated()
            ->assertJsonPath('type', 'digital');
    }

    public function test_seller_can_create_service_product(): void
    {
        $seller = $this->createSellerWithContract();

        $response = $this->actingAs($seller)
            ->postJson('/api/products', [
                'type' => 'service',
                'name' => 'Consultoría Agrícola',
                'description' => 'Asesoría en cultivos',
                'price' => 150.00,
                'serviceDuration' => 60,
                'serviceModality' => 'presencial',
                'serviceLocation' => 'Lima',
            ]);

        $response->assertCreated()
            ->assertJsonPath('type', 'service');
    }

    public function test_digital_product_requires_download_url(): void
    {
        $seller = $this->createSellerWithContract();

        $response = $this->actingAs($seller)
            ->postJson('/api/products', [
                'type' => 'digital',
                'name' => 'Digital Sin URL',
                'price' => 10.00,
            ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['downloadUrl']);
    }

    public function test_service_product_requires_duration_and_modality(): void
    {
        $seller = $this->createSellerWithContract();

        $response = $this->actingAs($seller)
            ->postJson('/api/products', [
                'type' => 'service',
                'name' => 'Service Sin Campos',
                'price' => 50.00,
            ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['serviceDuration', 'serviceModality']);
    }

    public function test_customer_cannot_create_product(): void
    {
        $customer = $this->createCustomer();

        $response = $this->actingAs($customer)
            ->postJson('/api/products', [
                'type' => 'physical',
                'name' => 'Test',
                'price' => 10,
            ]);

        $response->assertForbidden();
    }

    public function test_create_product_with_category_and_attributes(): void
    {
        $seller = $this->createSellerWithContract();
        Category::factory()->create(['slug' => 'semillas']);

        $response = $this->actingAs($seller)
            ->postJson('/api/products', [
                'type' => 'physical',
                'name' => 'Quinua Premium',
                'price' => 30.00,
                'stock' => 50,
                'category' => 'semillas',
                'mainAttributes' => [
                    ['values' => ['500g', '1kg']],
                ],
                'additionalAttributes' => [
                    ['values' => ['Orgánico', 'Sin GMO']],
                ],
            ]);

        $response->assertCreated();
        $productId = $response->json('id');
        $product = Product::find($productId);
        $this->assertCount(1, $product->mainAttributes);
        $this->assertCount(1, $product->additionalAttributes);
        $this->assertCount(1, $product->categories);
    }

    // ─── UPDATE (SELLER) ────────────────────────────────────────────

    public function test_seller_can_update_product(): void
    {
        $seller = $this->createSellerWithContract();
        $store = $seller->ownedStores()->first();
        $product = Product::factory()->create(['store_id' => $store->id]);

        $response = $this->actingAs($seller)
            ->putJson("/api/products/{$product->id}", [
                'name' => 'Updated Product Name',
                'price' => 99.99,
            ]);

        $response->assertOk()
            ->assertJsonPath('name', 'Updated Product Name');
    }

    // ─── DESTROY (SELLER) ───────────────────────────────────────────

    public function test_seller_can_delete_product(): void
    {
        $seller = $this->createSellerWithContract();
        $store = $seller->ownedStores()->first();
        $product = Product::factory()->create(['store_id' => $store->id]);

        $response = $this->actingAs($seller)
            ->deleteJson("/api/products/{$product->id}");

        $response->assertOk()
            ->assertJsonPath('success', true);

        $this->assertSoftDeleted('products', ['id' => $product->id]);
    }

    // ─── UPDATE STOCK (SELLER) ──────────────────────────────────────

    public function test_seller_can_update_stock(): void
    {
        $seller = $this->createSellerWithContract();
        $store = $seller->ownedStores()->first();
        $product = Product::factory()->create([
            'store_id' => $store->id,
            'stock' => 10,
        ]);

        $response = $this->actingAs($seller)
            ->putJson("/api/products/{$product->id}/stock", [
                'quantity' => 50,
            ]);

        $response->assertOk();
        $this->assertEquals(50, $product->fresh()->stock);
    }

    public function test_update_stock_requires_non_negative(): void
    {
        $seller = $this->createSellerWithContract();
        $store = $seller->ownedStores()->first();
        $product = Product::factory()->create(['store_id' => $store->id]);

        $response = $this->actingAs($seller)
            ->putJson("/api/products/{$product->id}/stock", [
                'quantity' => -5,
            ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['quantity']);
    }

    // ─── UPDATE STATUS (ADMIN) ──────────────────────────────────────

    public function test_admin_can_approve_product(): void
    {
        $admin = $this->createAdmin();
        $store = Store::factory()->approved()->create();
        $product = Product::factory()->pendingReview()->create(['store_id' => $store->id]);

        $response = $this->actingAs($admin)
            ->putJson("/api/products/{$product->id}/status", [
                'status' => 'approved',
            ]);

        $response->assertOk();
        $this->assertEquals('approved', $product->fresh()->status);
    }

    public function test_admin_can_reject_product(): void
    {
        $admin = $this->createAdmin();
        $store = Store::factory()->approved()->create();
        $product = Product::factory()->pendingReview()->create(['store_id' => $store->id]);

        $response = $this->actingAs($admin)
            ->putJson("/api/products/{$product->id}/status", [
                'status' => 'rejected',
                'reason' => 'No cumple estándares',
            ]);

        $response->assertOk();
        $this->assertEquals('rejected', $product->fresh()->status);
    }

    public function test_seller_cannot_change_product_status(): void
    {
        $seller = $this->createSeller();
        $store = $seller->ownedStores()->first();
        $product = Product::factory()->pendingReview()->create(['store_id' => $store->id]);

        $response = $this->actingAs($seller)
            ->putJson("/api/products/{$product->id}/status", [
                'status' => 'approved',
            ]);

        $response->assertForbidden();
    }
}
