<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\WishlistResource;
use App\Models\Product;
use App\Models\Wishlist;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class WishlistController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();

        $items = Wishlist::where('user_id', $user->id)
            ->with('product.store')
            ->orderBy('created_at', 'desc')
            ->get();

        return $this->success(WishlistResource::collection($items));
    }

    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'product_id' => 'required|exists:products,id',
        ]);

        $user = $request->user();

        $exists = Wishlist::where('user_id', $user->id)
            ->where('product_id', $request->product_id)
            ->exists();

        if ($exists) {
            return $this->error('El producto ya está en tu lista de deseos.', 400);
        }

        $wishlist = Wishlist::create([
            'user_id' => $user->id,
            'product_id' => $request->product_id,
        ]);

        $wishlist->load('product.store');

        return $this->created(new WishlistResource($wishlist));
    }

    public function destroy(Request $request, string $id): JsonResponse
    {
        $user = $request->user();

        $item = Wishlist::where('id', $id)
            ->where('user_id', $user->id)
            ->firstOrFail();

        $item->delete();

        return $this->success(null, 'Producto eliminado de la lista de deseos.');
    }

    public function check(Request $request): JsonResponse
    {
        $request->validate([
            'product_id' => 'required|exists:products,id',
        ]);

        $user = $request->user();

        $exists = Wishlist::where('user_id', $user->id)
            ->where('product_id', $request->product_id)
            ->exists();

        $item = Wishlist::where('user_id', $user->id)
            ->where('product_id', $request->product_id)
            ->first();

        return $this->success([
            'in_wishlist' => $exists,
            'wishlist_id' => $item?->id,
        ]);
    }
}
