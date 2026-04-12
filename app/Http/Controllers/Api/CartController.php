<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Cart\AddToCartRequest;
use App\Http\Resources\CartResource;
use App\Models\Cart;
use App\Models\CartItem;
use App\Models\Product;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class CartController extends Controller
{
    private function getOrCreateCart(Request $request): Cart
    {
        $user = $request->user();

        if ($user) {
            return Cart::firstOrCreate(['user_id' => $user->id], [
                'session_id' => null,
            ]);
        }

        $sessionId = $request->header('X-Session-ID') ?? $request->session()->getId();

        return Cart::firstOrCreate(['session_id' => $sessionId], [
            'user_id' => null,
        ]);
    }

    public function index(Request $request): JsonResponse
    {
        $cart = $this->getOrCreateCart($request);
        $cart->load('items.product');

        return $this->success(new CartResource($cart));
    }

    public function addItem(AddToCartRequest $request): JsonResponse
    {
        $data = $request->validated();
        $cart = $this->getOrCreateCart($request);

        $product = Product::findOrFail($data['product_id']);

        if ($product->status !== 'approved') {
            return $this->error('Este producto no está disponible.', 422);
        }

        if ($product->stock < ($data['quantity'] ?? 1)) {
            return $this->error('Stock insuficiente.', 422);
        }

        $cartItem = CartItem::where('cart_id', $cart->id)
            ->where('product_id', $product->id)
            ->first();

        if ($cartItem) {
            $newQuantity = $cartItem->quantity + ($data['quantity'] ?? 1);
            if ($newQuantity > $product->stock) {
                return $this->error('Stock insuficiente.', 422);
            }
            $cartItem->update(['quantity' => $newQuantity]);
        } else {
            $cartItem = CartItem::create([
                'cart_id' => $cart->id,
                'product_id' => $product->id,
                'quantity' => $data['quantity'] ?? 1,
            ]);
        }

        $cart->load('items.product');

        return $this->success(new CartResource($cart));
    }

    public function updateItem(Request $request, int $productId): JsonResponse
    {
        $data = $request->validate([
            'quantity' => 'required|integer|min:1|max:99',
        ]);

        $cart = $this->getOrCreateCart($request);

        $cartItem = CartItem::where('cart_id', $cart->id)
            ->where('product_id', $productId)
            ->first();

        if (! $cartItem) {
            return $this->notFound('Producto no encontrado en el carrito');
        }

        $product = $cartItem->product;

        if ($product->stock < $data['quantity']) {
            return $this->error('Stock insuficiente.', 422);
        }

        $cartItem->update(['quantity' => $data['quantity']]);

        $cart->load('items.product');

        return $this->success(new CartResource($cart));
    }

    public function removeItem(Request $request, int $productId): JsonResponse
    {
        $cart = $this->getOrCreateCart($request);

        CartItem::where('cart_id', $cart->id)
            ->where('product_id', $productId)
            ->delete();

        $cart->load('items.product');

        return $this->success(new CartResource($cart));
    }

    public function clear(Request $request): JsonResponse
    {
        $cart = $this->getOrCreateCart($request);
        $cart->items()->delete();
        $cart->load('items.product');

        return $this->success(new CartResource($cart));
    }
}
