<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Product;
use App\Models\Store;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Tests\Traits\WithRoles;

final class CategoryTest extends TestCase
{
    use RefreshDatabase, WithRoles;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedRoles();
    }

    // ─── INDEX (PUBLIC) ─────────────────────────────────────────────

    public function test_list_categories_returns_all(): void
    {
        Category::factory()->count(3)->create();

        $response = $this->getJson('/api/categories');

        $response->assertOk()
            ->assertJsonCount(3, 'data');
    }

    public function test_list_categories_as_tree(): void
    {
        $parent = Category::factory()->create();
        Category::factory()->child($parent)->count(2)->create();
        Category::factory()->create(); // another root

        $response = $this->getJson('/api/categories?tree=1');

        $response->assertOk()
            ->assertJsonCount(2, 'data'); // 2 root categories
    }

    public function test_list_categories_hide_empty(): void
    {
        $catWithProducts = Category::factory()->create();
        Category::factory()->create(); // empty

        $store = Store::factory()->approved()->create();
        $product = Product::factory()->approved()->create(['store_id' => $store->id]);
        $catWithProducts->products()->attach($product);

        $response = $this->getJson('/api/categories?hide_empty=1');

        $response->assertOk()
            ->assertJsonCount(1, 'data');
    }

    // ─── SHOW (PUBLIC) ──────────────────────────────────────────────

    public function test_show_category_with_children(): void
    {
        $parent = Category::factory()->create();
        Category::factory()->child($parent)->count(2)->create();

        $response = $this->getJson("/api/categories/{$parent->id}");

        $response->assertOk()
            ->assertJsonPath('id', $parent->id)
            ->assertJsonPath('name', $parent->name);
    }

    public function test_show_nonexistent_category_returns_404(): void
    {
        $response = $this->getJson('/api/categories/9999');

        $response->assertNotFound();
    }

    // ─── STORE (ADMIN) ──────────────────────────────────────────────

    public function test_admin_can_create_category(): void
    {
        $admin = $this->createAdmin();

        $response = $this->actingAs($admin)
            ->postJson('/api/categories', [
                'name' => 'Semillas Orgánicas',
                'description' => 'Semillas certificadas',
            ]);

        $response->assertCreated()
            ->assertJsonPath('name', 'Semillas Orgánicas')
            ->assertJsonPath('slug', 'semillas-organicas');

        $this->assertDatabaseHas('categories', ['name' => 'Semillas Orgánicas']);
    }

    public function test_admin_can_create_subcategory(): void
    {
        $admin = $this->createAdmin();
        $parent = Category::factory()->create();

        $response = $this->actingAs($admin)
            ->postJson('/api/categories', [
                'name' => 'Sub Category',
                'parent' => $parent->id,
            ]);

        $response->assertCreated();
        $this->assertDatabaseHas('categories', [
            'name' => 'Sub Category',
            'parent_id' => $parent->id,
        ]);
    }

    public function test_seller_cannot_create_category(): void
    {
        $seller = $this->createSeller();

        $response = $this->actingAs($seller)
            ->postJson('/api/categories', ['name' => 'Forbidden']);

        $response->assertForbidden();
    }

    public function test_unauthenticated_cannot_create_category(): void
    {
        $response = $this->postJson('/api/categories', ['name' => 'Test']);

        $response->assertUnauthorized();
    }

    public function test_create_category_requires_name(): void
    {
        $admin = $this->createAdmin();

        $response = $this->actingAs($admin)
            ->postJson('/api/categories', []);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['name']);
    }

    // ─── UPDATE (ADMIN) ─────────────────────────────────────────────

    public function test_admin_can_update_category(): void
    {
        $admin = $this->createAdmin();
        $category = Category::factory()->create(['name' => 'Old Name']);

        $response = $this->actingAs($admin)
            ->putJson("/api/categories/{$category->id}", [
                'name' => 'New Name',
            ]);

        $response->assertOk()
            ->assertJsonPath('name', 'New Name');
    }

    // ─── DESTROY (ADMIN) ────────────────────────────────────────────

    public function test_admin_can_delete_category(): void
    {
        $admin = $this->createAdmin();
        $category = Category::factory()->create();

        $response = $this->actingAs($admin)
            ->deleteJson("/api/categories/{$category->id}");

        $response->assertOk();
        $this->assertDatabaseMissing('categories', ['id' => $category->id]);
    }

    public function test_deleting_category_detaches_products(): void
    {
        $admin = $this->createAdmin();
        $category = Category::factory()->create();
        $store = Store::factory()->approved()->create();
        $product = Product::factory()->create(['store_id' => $store->id]);
        $category->products()->attach($product);

        $this->actingAs($admin)
            ->deleteJson("/api/categories/{$category->id}");

        $this->assertDatabaseMissing('category_product', [
            'category_id' => $category->id,
        ]);
    }
}
