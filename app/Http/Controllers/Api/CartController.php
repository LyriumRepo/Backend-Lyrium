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
        // Plan A: Intentar obtener el usuario de la forma estándar de Sanctum
        $user = auth('sanctum')->user();

        // Plan B: Si Laravel devuelve null (por el conflicto de cookies de Insomnia)
        // pero verificamos que sí enviaste un Bearer Token, lo interceptamos manualmente.
        if (! $user && $request->bearerToken()) {
            $token = \Laravel\Sanctum\PersonalAccessToken::findToken($request->bearerToken());
            if ($token) {
                $user = $token->tokenable;
            }
        }

        $sessionId = $request->header('X-Session-ID') ?? '';

        if (! $user) {
            return Cart::firstOrCreate(
                ['session_id' => $sessionId],
                ['user_id' => null]
            );
        }
        $userCart = Cart::firstOrCreate(
            ['user_id' => $user->id],
            ['session_id' => null] 
        );
        $guestCart = Cart::where('session_id', $sessionId)
            ->whereNull('user_id')
            ->first();

        if ($guestCart) {
            $guestItems = CartItem::where('cart_id', $guestCart->id)->get();

            if ($guestItems->isNotEmpty()) {
                $isRecent = $guestCart->created_at && $guestCart->created_at->gt(now()->subHours(24));
                $userHasItems = CartItem::where('cart_id', $userCart->id)->exists();

                if (! $isRecent && $userHasItems) {
                    foreach ($guestItems as $gi) {
                        $gi->delete();
                    }
                } else {
                    foreach ($guestItems as $guestItem) {
                        $existingUserItem = CartItem::where('cart_id', $userCart->id)
                            ->where('product_id', $guestItem->product_id)
                            ->first();

                        if ($existingUserItem) {
                            $newQuantity = $existingUserItem->quantity + $guestItem->quantity;
                            $product = $guestItem->product;
                            if ($product && $newQuantity > $product->stock) {
                                $newQuantity = $product->stock;
                            }
                            $existingUserItem->update(['quantity' => $newQuantity]);
                            $guestItem->delete();
                        } else {
                            $guestItem->update(['cart_id' => $userCart->id]);
                        }
                    }
                }
            }

            $guestCart->delete();
        }

        return $userCart;
    }

    public function index(Request $request): JsonResponse
    {
        $cart = $this->getOrCreateCart($request);
        $cart->load('items.product.store');

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
        $cart->load('items.product.store');

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
        $cart->load('items.product.store');

        return $this->success(new CartResource($cart));
    }

    public function removeItem(Request $request, int $productId): JsonResponse
    {
        $cart = $this->getOrCreateCart($request);

        CartItem::where('cart_id', $cart->id)
            ->where('product_id', $productId)
            ->delete();
        $cart->load('items.product.store');

        return $this->success(new CartResource($cart));
    }

    public function clear(Request $request): JsonResponse
    {
        $cart = $this->getOrCreateCart($request);
        $cart->items()->delete();
        $cart->load('items.product.store');

        return $this->success(new CartResource($cart));
    }
}
