<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Http\Resources\InvoiceResource;
use App\Models\Invoice;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Store;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Tests\TestCase;

/**
 * Regression test for: "items.map is not a function" crash in
 * InvoiceOrderItemsSection.tsx.
 *
 * Root cause: InvoiceResource::combinedOrderItems() filtered the order's
 * items collection with ->where('store_id', ...), which preserves the
 * *original* array keys instead of reindexing. On a multi-store order where
 * the invoice's store is not the first store in the collection, this leaves
 * non-sequential keys (e.g. {1: item}). PHP's json_encode() then serializes
 * such an array as a JSON object ({"1": {...}}) instead of a JSON array
 * ([{...}]), so the frontend receives `order.items` as a non-array object
 * and `items.map()` throws at runtime.
 *
 * Also covers a second, related gap found while verifying the multi-store
 * invoicing flow: the `order.stores` field (which powers the "+N tiendas"
 * chip in AdminInvoiceDrawer.tsx) reused the same store_id-filtered
 * combinedOrderItems(), so it could only ever resolve to the invoice's own
 * store — the chip could never fire for a real multi-store order. Fixed via
 * a dedicated allOrderStores() that reads all order items unfiltered.
 *
 * Builds every model in memory (no ->save(), no DB, no migrations) so this
 * runs independent of the project's known SQLite/MySQL migration
 * incompatibility (`ALTER TABLE ... MODIFY` unsupported on sqlite) that
 * currently blocks RefreshDatabase-based feature tests.
 */
final class InvoiceResourceCombinedItemsTest extends TestCase
{
    public function test_order_items_serialize_as_a_json_array_when_invoice_store_is_not_first_in_the_order(): void
    {
        $storeA = (new Store())->forceFill(['id' => 1, 'store_name' => 'Tienda A', 'slug' => 'tienda-a']);
        $storeB = (new Store())->forceFill(['id' => 2, 'store_name' => 'Tienda B', 'slug' => 'tienda-b']);

        // storeA's item is inserted FIRST (index 0), storeB's item SECOND (index 1).
        // The invoice below belongs to storeB, so filtering by store_id must skip
        // index 0 and keep only index 1 — exactly the shape that broke json_encode.
        $itemA = (new OrderItem())->forceFill([
            'id' => 1, 'order_id' => 1, 'product_id' => 1, 'store_id' => 1,
            'product_name' => 'Producto Tienda A', 'unit_price' => 100, 'quantity' => 1, 'line_total' => 100,
        ]);
        $itemA->setRelation('store', $storeA);
        $itemA->setRelation('product', null);

        $itemB = (new OrderItem())->forceFill([
            'id' => 2, 'order_id' => 1, 'product_id' => 2, 'store_id' => 2,
            'product_name' => 'Producto Tienda B', 'unit_price' => 100, 'quantity' => 1, 'line_total' => 100,
        ]);
        $itemB->setRelation('store', $storeB);
        $itemB->setRelation('product', null);

        $order = (new Order())->forceFill([
            'id' => 1, 'order_number' => 'ORD-TEST-0001', 'user_id' => 1,
            'status' => Order::STATUS_CONFIRMED, 'total' => 200,
        ]);
        $order->setRelation('items', new Collection([$itemA, $itemB]));
        $order->setRelation('serviceItems', new Collection());

        $invoice = (new Invoice())->forceFill([
            'id' => 1, 'order_id' => 1, 'invoice_number' => 'INV-TEST-0001',
            'provider' => 'nubefact', 'store_id' => 2, 'total' => 100, 'status' => 'DRAFT',
        ]);
        $invoice->setRelation('order', $order);
        $invoice->setRelation('store', null);

        $resource = (new InvoiceResource($invoice))->toArray(Request::create('/'));

        $items = $resource['order']['items'];

        $this->assertInstanceOf(Collection::class, $items);
        $this->assertCount(1, $items);
        $this->assertSame('Producto Tienda B', $items->first()['productName']);

        // The real bug: json_encode() serializes a Collection whose underlying
        // keys aren't a sequential 0-based list as a JSON object, not an array.
        // array_is_list() on the raw items array catches exactly that shape
        // mismatch — this is the assertion that would have caught the regression.
        $this->assertTrue(
            array_is_list($items->all()),
            'order.items must serialize as a JSON array (list), not an object — '.
            'otherwise `items.map()` throws on the frontend.',
        );

        // End-to-end: confirm the actual json_encode() output is a JSON array,
        // not an object, for the exact value this resource returns over the wire.
        $encoded = json_encode($items);
        $this->assertStringStartsWith('[', $encoded);
    }

    public function test_order_stores_lists_every_store_in_the_order_with_the_invoice_store_first(): void
    {
        $storeA = (new Store())->forceFill(['id' => 1, 'store_name' => 'Mas Natural', 'slug' => 'mas-natural']);
        $storeB = (new Store())->forceFill(['id' => 2, 'store_name' => 'Biotienda', 'slug' => 'biotienda']);

        $itemA = (new OrderItem())->forceFill([
            'id' => 1, 'order_id' => 1, 'product_id' => 1, 'store_id' => 1,
            'product_name' => 'Producto A', 'unit_price' => 100, 'quantity' => 1, 'line_total' => 100,
        ]);
        $itemA->setRelation('store', $storeA);
        $itemA->setRelation('product', null);

        $itemB = (new OrderItem())->forceFill([
            'id' => 2, 'order_id' => 1, 'product_id' => 2, 'store_id' => 2,
            'product_name' => 'Producto B', 'unit_price' => 100, 'quantity' => 1, 'line_total' => 100,
        ]);
        $itemB->setRelation('store', $storeB);
        $itemB->setRelation('product', null);

        $order = (new Order())->forceFill([
            'id' => 1, 'order_number' => 'ORD-TEST-0002', 'user_id' => 1,
            'status' => Order::STATUS_CONFIRMED, 'total' => 200,
        ]);
        $order->setRelation('items', new Collection([$itemA, $itemB]));
        $order->setRelation('serviceItems', new Collection());

        // Invoice belongs to storeA (Mas Natural). Viewing it should still
        // surface Biotienda as "the other store in this order" for the chip —
        // without exposing Biotienda's items/prices anywhere in this payload.
        $invoice = (new Invoice())->forceFill([
            'id' => 1, 'order_id' => 1, 'invoice_number' => 'INV-TEST-0002',
            'provider' => 'nubefact', 'store_id' => 1, 'total' => 100, 'status' => 'DRAFT',
        ]);
        $invoice->setRelation('order', $order);
        $invoice->setRelation('store', null);

        $resource = (new InvoiceResource($invoice))->toArray(Request::create('/'));

        $stores = $resource['order']['stores'];
        $this->assertCount(2, $stores);
        $this->assertSame('Mas Natural', $stores->get(0)['name'], 'the invoice\'s own store must be first (drives the chip label)');
        $this->assertSame('Biotienda', $stores->get(1)['name'], 'the other store in the order must still be listed (drives the "+1" badge)');

        // And confirm the items table stays scoped to ONLY this invoice's own
        // store — the fix must not leak Biotienda's items/prices into it.
        $items = $resource['order']['items'];
        $this->assertCount(1, $items);
        $this->assertSame('Producto A', $items->first()['productName']);
    }
}
