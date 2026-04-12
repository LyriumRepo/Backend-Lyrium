<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Models\Product;
use App\Models\Store;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class ProductModelTest extends TestCase
{
    use RefreshDatabase;

    public function test_is_physical(): void
    {
        $product = Product::factory()->physical()->create(['store_id' => Store::factory()]);

        $this->assertTrue($product->isPhysical());
        $this->assertFalse($product->isDigital());
        $this->assertFalse($product->isService());
    }

    public function test_is_digital(): void
    {
        $product = Product::factory()->digital()->create(['store_id' => Store::factory()]);

        $this->assertTrue($product->isDigital());
        $this->assertFalse($product->isPhysical());
    }

    public function test_is_service(): void
    {
        $product = Product::factory()->service()->create(['store_id' => Store::factory()]);

        $this->assertTrue($product->isService());
        $this->assertFalse($product->isPhysical());
    }

    public function test_decrement_stock(): void
    {
        $product = Product::factory()->create([
            'store_id' => Store::factory(),
            'stock' => 50,
        ]);

        $product->decrementStock(10);

        $this->assertEquals(40, $product->fresh()->stock);
    }

    public function test_product_soft_deletes(): void
    {
        $product = Product::factory()->create(['store_id' => Store::factory()]);
        $product->delete();

        $this->assertSoftDeleted('products', ['id' => $product->id]);
    }
}
