<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\ProductBranchStock;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class ProductBranchStockController extends Controller
{
    /**
     * GET /api/products/{id}/branches
     * Retorna las sucursales del store del producto con su stock para RT.
     * Si el usuario es cliente (o público), solo muestra branches con pickup_enabled.
     */
    public function index(Request $request, int $id): JsonResponse
    {
        $product = Product::findOrFail($id);
        $store = $product->store;

        if (! $store) {
            return response()->json(['message' => 'Producto sin tienda asociada.'], 404);
        }

        $branches = $store->branches()->where('is_active', true)->get();
        $branchStock = $product->branchStock()->pluck('stock', 'store_branch_id');
        $branchPickup = $product->branchStock()->pluck('pickup_enabled', 'store_branch_id');

        $isSeller = $request->user()?->hasAnyRole(['seller', 'administrator']);

        $data = $branches->map(function ($branch) use ($branchStock, $branchPickup, $isSeller) {
            $stock = $branchStock->get($branch->id, 0);
            $pickupEnabled = $branchPickup->get($branch->id, false);

            if (! $isSeller && ! $pickupEnabled) {
                return null;
            }

            return [
                'id' => $branch->id,
                'name' => $branch->name,
                'address' => $branch->address,
                'district' => $branch->district,
                'province' => $branch->province,
                'department' => $branch->department,
                'phone' => $branch->phone,
                'hours' => $branch->hours,
                'is_principal' => $branch->is_principal,
                'maps_url' => $branch->maps_url,
                'branch_stock' => $stock,
                'pickup_enabled' => $pickupEnabled,
            ];
        })->filter()->values();

        return response()->json(['data' => $data]);
    }

    /**
     * PUT /api/products/{id}/branches
     * Sincroniza el stock por sucursal para un producto.
     * Body: { branches: [{ branch_id, stock, pickup_enabled }] }
     */
    public function update(Request $request, int $id): JsonResponse
    {
        $product = Product::findOrFail($id);
        $user = $request->user();

        if (! $user->hasRole('administrator')) {
            $store = $user->store;
            if (! $store || $store->id !== $product->store_id) {
                return response()->json(['message' => 'No autorizado.'], 403);
            }
        }

        $data = $request->validate([
            'branches' => 'required|array',
            'branches.*.branch_id' => 'required|integer|exists:store_branches,id',
            'branches.*.stock' => 'required|integer|min:0',
            'branches.*.pickup_enabled' => 'required|boolean',
        ]);

        foreach ($data['branches'] as $branch) {
            ProductBranchStock::updateOrCreate(
                [
                    'product_id' => $product->id,
                    'store_branch_id' => $branch['branch_id'],
                ],
                [
                    'stock' => $branch['stock'],
                    'pickup_enabled' => $branch['pickup_enabled'],
                ]
            );
        }

        // Eliminar registros que ya no están en el request
        $incomingBranchIds = collect($data['branches'])->pluck('branch_id')->toArray();
        ProductBranchStock::where('product_id', $product->id)
            ->whereNotIn('store_branch_id', $incomingBranchIds)
            ->delete();

        return response()->json(['message' => 'Stock por sucursal actualizado.']);
    }

    /**
     * GET /api/seller/products/branch-stock
     * Retorna el stock total de retiro en tienda (suma de stock en todas las sucursales)
     * para todos los productos del vendedor autenticado.
     * Respuesta: { data: { "product_id": total_stock, ... } }
     */
    public function sellerBranchStock(Request $request): JsonResponse
    {
        $user = $request->user();
        $store = $user->store;

        if (! $store) {
            return response()->json(['data' => []]);
        }

        $branchStock = ProductBranchStock::whereHas('product', function ($q) use ($store) {
            $q->where('store_id', $store->id);
        })->get();

        $result = $branchStock->groupBy('product_id')->map(function ($stocks) {
            return $stocks->where('pickup_enabled', true)->sum('stock');
        });

        return response()->json(['data' => $result]);
    }

    /**
     * GET /api/products/{id}/branches/public
     * Endpoint público (sin auth) para que el checkout vea sucursales disponibles.
     */
    public function publicIndex(int $id): JsonResponse
    {
        $product = Product::findOrFail($id);
        $store = $product->store;

        if (! $store) {
            return response()->json(['data' => []]);
        }

        $branches = $store->branches()->where('is_active', true)->get();
        $branchStock = $product->branchStock()
            ->where('pickup_enabled', true)
            ->pluck('stock', 'store_branch_id');

        $data = $branches->filter(function ($branch) use ($branchStock) {
            return $branchStock->has($branch->id);
        })->map(function ($branch) use ($branchStock) {
            return [
                'id' => $branch->id,
                'name' => $branch->name,
                'address' => $branch->address,
                'district' => $branch->district,
                'province' => $branch->province,
                'phone' => $branch->phone,
                'hours' => $branch->hours,
                'maps_url' => $branch->maps_url,
                'branch_stock' => $branchStock->get($branch->id, 0),
            ];
        })->values();

        return response()->json(['data' => $data]);
    }
}
