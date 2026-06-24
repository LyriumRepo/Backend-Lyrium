<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\WishlistResource;
use App\Models\Wishlist;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class WishlistController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $items = Wishlist::where('user_id', $request->user()->id)
            ->with('product.store')
            ->orderBy('created_at', 'desc')
            ->get();

        return $this->success(WishlistResource::collection($items));
    }

    public function store(Request $request): JsonResponse
    {
        $request->validate(['product_id' => 'required|exists:products,id']);

        $productId = (int) $request->product_id;

        $exists = Wishlist::where('user_id', $request->user()->id)
            ->where('product_id', $productId)
            ->exists();

        if ($exists) {
            return $this->error('El producto ya está en tu lista de deseos.', 400);
        }

        $wishlist = Wishlist::create([
            'user_id' => $request->user()->id,
            'product_id' => $productId,
        ]);

        $wishlist->load('product.store');

        return $this->created(new WishlistResource($wishlist));
    }

    public function destroy(Request $request, string $id): JsonResponse
    {
        $item = Wishlist::where('user_id', $request->user()->id)
            ->findOrFail($id);

        $item->delete();

        return $this->noContent();
    }

    public function check(Request $request): JsonResponse
    {
        $productId = (int) $request->query('product_id');

        if (! $productId) {
            return $this->error('product_id es requerido.', 400);
        }

        $item = Wishlist::where('user_id', $request->user()->id)
            ->where('product_id', $productId)
            ->first();

        return $this->success([
            'in_wishlist' => $item !== null,
            'wishlist_id' => $item?->id,
        ]);
    }
}
