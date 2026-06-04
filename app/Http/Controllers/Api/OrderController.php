<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Events\NewConversationMessage;
use App\Events\NewOrderReceived;
use App\Events\OrderPaymentConfirmed;
use App\Events\OrderStatusChanged;
use App\Notifications\OrderCreatedNotification;
use App\Notifications\NewOrderSellerNotification;
use App\Models\Store;
use App\Http\Controllers\Controller;
use App\Http\Requests\Order\CreateOrderRequest;
use App\Http\Requests\Order\UpdateOrderStatusRequest;
use App\Http\Resources\ConversationResource;
use App\Http\Resources\OrderResource;
use App\Models\Cart;
use App\Models\Conversation;
use App\Models\ConversationMessage;
use App\Models\Coupon;
use App\Models\CouponUsage;
use App\Models\Invoice;
use App\Models\Order;
use App\Models\OrderItem;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

final class OrderController extends Controller
{
    public function activeCount(Request $request): JsonResponse
    {
        $user = $request->user();

        $activeStatuses = [
            Order::STATUS_PENDING_SELLER,
            Order::STATUS_CONFIRMED,
            Order::STATUS_PROCESSING,
            Order::STATUS_SHIPPED,
        ];

        $count = Order::where('user_id', $user->id)
            ->whereIn('status', $activeStatuses)
            ->count();

        return $this->success(['count' => $count]);
    }

    public function index(Request $request): JsonResponse
    {
        $user = $request->user();

        if ($user->hasRole('administrator')) {
            $orders = Order::with(['items.product', 'items.store', 'user'])
                ->orderBy('created_at', 'desc')
                ->paginate(15);
        } elseif ($user->hasRole('seller')) {
            $storeIds = $user->stores()->pluck('stores.id');
            $orders = Order::whereHas('items', fn($q) => $q->whereIn('store_id', $storeIds))
                ->with(['items.product', 'items.store', 'user'])
                ->orderBy('created_at', 'desc')
                ->paginate(15);
        } else {
            $orders = Order::where('user_id', $user->id)
                ->with(['items.product', 'items.store', 'user'])
                ->orderBy('created_at', 'desc')
                ->paginate(15);
        }

        return $this->success([
            'data' => OrderResource::collection($orders),
            'pagination' => [
                'page' => $orders->currentPage(),
                'perPage' => $orders->perPage(),
                'total' => $orders->total(),
                'totalPages' => $orders->lastPage(),
                'hasMore' => $orders->hasMorePages(),
            ],
        ]);
    }

    public function show(Request $request, string $id): JsonResponse
    {
        $user = $request->user();
        $order = Order::with(['items.product', 'items.store', 'user'])->findOrFail($id);

        if (! $user->hasRole('administrator') && ! $user->hasRole('seller') && $order->user_id !== $user->id) {
            return $this->forbidden('No tienes acceso a esta orden.');
        }

        if ($user->hasRole('seller')) {
            $storeIds = $user->stores()->pluck('stores.id');
            $hasAccess = $order->items->every(fn($item) => $storeIds->contains($item->store_id));
            if (! $hasAccess) {
                return $this->forbidden('No tienes acceso a esta orden.');
            }
        }

        return $this->success(new OrderResource($order));
    }

    public function store(CreateOrderRequest $request): JsonResponse
    {
        $data = $request->validated();
        $user = $request->user();

        $cart = Cart::where('user_id', $user->id)->with('items.product')->first();

        if (! $cart || $cart->items->isEmpty()) {
            return $this->error('El carrito está vacío.', 400);
        }

        $order = DB::transaction(function () use ($data, $user, $cart) {
            $subtotal = 0;
            $orderItems = [];

            foreach ($cart->items as $item) {
                $product = $item->product;

                if ($product->status !== 'approved') {
                    throw new \Exception("El producto '{$product->name}' no está disponible.");
                }

                if ($product->stock < $item->quantity) {
                    throw new \Exception("Stock insuficiente para '{$product->name}'.");
                }

                $lineTotal = $item->quantity * $product->price;
                $subtotal += $lineTotal;

                $orderItems[] = [
                    'product_id' => $product->id,
                    'store_id' => $product->store_id,
                    'product_name' => $product->name,
                    'unit_price' => $product->price,
                    'quantity' => $item->quantity,
                    'line_total' => $lineTotal,
                    'status' => 'pending_seller',
                ];

                $product->decrement('stock', $item->quantity);
            }

            if ($subtotal < Order::MIN_ORDER_AMOUNT) {
                throw new \Exception('El monto mínimo de la orden es S/ ' . number_format(Order::MIN_ORDER_AMOUNT, 2) . '.');
            }

            $shippingCost = $data['shipping_cost'] ?? 0;
            $taxRate = 0.16;
            $taxAmount = round($subtotal * $taxRate, 2);
            $discountAmount = 0;
            $couponId = null;
            $couponCode = null;

            if (! empty($data['coupon_code'])) {
                $coupon = Coupon::findByCode($data['coupon_code']);

                if (! $coupon) {
                    throw new \Exception('El cupón no existe.');
                }

                if (! $coupon->isValid()) {
                    throw new \Exception('El cupón no es válido o ha expirado.');
                }

                if (! $coupon->isValidForUser($user->id)) {
                    throw new \Exception('Ya has usado este cupón el número máximo de veces.');
                }

                $discountAmount = $coupon->calculateDiscount($subtotal);
                $couponId = $coupon->id;
                $couponCode = $coupon->code;
            }

            $total = $subtotal + $shippingCost + $taxAmount - $discountAmount;

            if ($total < 0) {
                $total = 0;
            }

            $order = Order::create([
                'order_number' => Order::generateOrderNumber(),
                'user_id' => $user->id,
                'status' => Order::STATUS_PENDING_SELLER,
                'payment_method' => $data['payment_method'] ?? null,
                'payment_status' => Order::PAYMENT_STATUS_PENDING,
                'shipping_name' => $data['shipping_name'] ?? null,
                'shipping_email' => $data['shipping_email'] ?? $user->email,
                'shipping_phone' => $data['shipping_phone'] ?? null,
                'shipping_address' => $data['shipping_address'] ?? null,
                'shipping_city' => $data['shipping_city'] ?? null,
                'shipping_postal_code' => $data['shipping_postal_code'] ?? null,
                'shipping_notes' => $data['shipping_notes'] ?? null,
                'shipping_type' => $data['shipping_type'] ?? null,
                'subtotal' => $subtotal,
                'shipping_cost' => $shippingCost,
                'tax_amount' => $taxAmount,
                'discount_amount' => $discountAmount,
                'total' => $total,
                'notes' => $data['notes'] ?? null,
                'coupon_code' => $couponCode,
                'coupon_id' => $couponId,
            ]);

            foreach ($orderItems as $item) {
                $order->items()->create($item);
            }

            if ($couponId) {
                $coupon->incrementUsage();
                CouponUsage::create([
                    'coupon_id' => $couponId,
                    'user_id' => $user->id,
                    'order_id' => $order->id,
                    'discount_amount' => $discountAmount,
                ]);
            }

            $cart->items()->delete();

            return $order;
        });

        $order->load(['items.product', 'items.store', 'user']);

        $user->notify(new OrderCreatedNotification($order));

        // Notificar a cada tienda involucrada en la orden
        $order->items->pluck('store_id')->unique()->each(function ($storeId) use ($order) {
            broadcast(new NewOrderReceived($order, $storeId));

            $store = Store::with('owner')->find($storeId);
            if ($store && $store->owner) {
                $store->owner->notify(new NewOrderSellerNotification($order, $store));
            }
        });

        broadcast(new OrderStatusChanged($order));

        return $this->created(new OrderResource($order));
    }

    public function confirm(Request $request, string $id): JsonResponse
    {
        $user = $request->user();

        if (! $user->hasRole('seller') && ! $user->hasRole('administrator')) {
            return $this->forbidden('Solo vendedores o administradores pueden confirmar órdenes.');
        }

        $order = Order::with(['items.product.store', 'user'])->findOrFail($id);

        if ($user->hasRole('seller')) {
            $storeIds = $user->stores()->pluck('stores.id');
            $hasAccess = $order->items()->whereIn('store_id', $storeIds)->exists();
            if (! $hasAccess) {
                return $this->forbidden('No tienes acceso a esta orden.');
            }

            $order->items()
                ->whereIn('store_id', $storeIds)
                ->where('status', OrderItem::STATUS_PENDING_SELLER)
                ->update(['status' => OrderItem::STATUS_CONFIRMED]);

            $order->refreshGlobalStatus();
        } else {
            $order->items()
                ->where('status', OrderItem::STATUS_PENDING_SELLER)
                ->update(['status' => OrderItem::STATUS_CONFIRMED]);
            $order->update(['status' => Order::STATUS_CONFIRMED]);
        }

        $order->load(['items.product.store', 'user']);

        broadcast(new OrderStatusChanged($order));

        return $this->success(new OrderResource($order));
    }

    public function updateStatus(UpdateOrderStatusRequest $request, string $id): JsonResponse
    {
        $data = $request->validated();
        $user = $request->user();
        $order = Order::with('items.product')->findOrFail($id);

        $newStatus = $data['status'];

        if (! in_array($newStatus, Order::STATUSES)) {
            return $this->error('Estado no válido.', 400);
        }

        if ($user->hasRole('seller')) {
            $storeIds = $user->stores()->pluck('stores.id');
            $hasAccess = $order->items()->whereIn('store_id', $storeIds)->exists();
            if (! $hasAccess) {
                return $this->forbidden('No tienes acceso a esta orden.');
            }

            $sellerAllowedStatuses = [
                Order::STATUS_PROCESSING,
                Order::STATUS_SHIPPED,
                Order::STATUS_DELIVERED,
                Order::STATUS_CANCELLED,
            ];

            if (! in_array($newStatus, $sellerAllowedStatuses)) {
                return $this->forbidden('No tienes permiso para cambiar a este estado.');
            }

            $validTransitions = [
                OrderItem::STATUS_CONFIRMED => [OrderItem::STATUS_PROCESSING, OrderItem::STATUS_CANCELLED],
                OrderItem::STATUS_PROCESSING => [OrderItem::STATUS_SHIPPED, OrderItem::STATUS_CANCELLED],
                OrderItem::STATUS_SHIPPED => [OrderItem::STATUS_DELIVERED, OrderItem::STATUS_CANCELLED],
            ];

            if ($newStatus === Order::STATUS_CANCELLED) {
                $itemsToCancel = $order->items()
                    ->whereIn('store_id', $storeIds)
                    ->whereIn('status', [OrderItem::STATUS_PENDING_SELLER, OrderItem::STATUS_CONFIRMED])
                    ->get();

                foreach ($itemsToCancel as $item) {
                    $item->update(['status' => OrderItem::STATUS_CANCELLED]);
                    $item->product->increment('stock', $item->quantity);
                }
            } else {
                $currentItemStatus = match ($newStatus) {
                    Order::STATUS_PROCESSING => OrderItem::STATUS_CONFIRMED,
                    Order::STATUS_SHIPPED => OrderItem::STATUS_PROCESSING,
                    Order::STATUS_DELIVERED => OrderItem::STATUS_SHIPPED,
                    default => null,
                };

                if ($currentItemStatus) {
                    $order->items()
                        ->whereIn('store_id', $storeIds)
                        ->where('status', $currentItemStatus)
                        ->update(['status' => match ($newStatus) {
                            Order::STATUS_PROCESSING => OrderItem::STATUS_PROCESSING,
                            Order::STATUS_SHIPPED => OrderItem::STATUS_SHIPPED,
                            Order::STATUS_DELIVERED => OrderItem::STATUS_DELIVERED,
                            default => $newStatus,
                        }]);
                }
            }

            $order->refreshGlobalStatus();
        } elseif ($user->hasRole('administrator')) {
            $order->update(['status' => $newStatus]);
            $order->items()->update(['status' => $newStatus]);
        } else {
            if ($order->user_id !== $user->id) {
                return $this->forbidden('No tienes acceso a esta orden.');
            }

            if ($newStatus === Order::STATUS_DELIVERED) {
                $order->items()
                    ->where('status', OrderItem::STATUS_SHIPPED)
                    ->update(['status' => OrderItem::STATUS_DELIVERED]);
                $order->refreshGlobalStatus();
            } elseif ($newStatus === Order::STATUS_CANCELLED) {
                $itemsToCancel = $order->items()
                    ->where('status', OrderItem::STATUS_PENDING_SELLER)
                    ->get();

                if ($itemsToCancel->isEmpty()) {
                    return $this->error('Solo puedes cancelar antes de que el vendedor confirme.', 400);
                }

                foreach ($itemsToCancel as $item) {
                    $item->update(['status' => OrderItem::STATUS_CANCELLED]);
                    $item->product->increment('stock', $item->quantity);
                }

                $order->refreshGlobalStatus();
            } else {
                return $this->forbidden('No tienes permiso para cambiar a este estado.');
            }
        }

        if (isset($data['payment_status'])) {
            $wasPaid = $order->payment_status === Order::PAYMENT_STATUS_PAID;
            $newPaymentStatus = $data['payment_status'];

            $order->update(['payment_status' => $newPaymentStatus]);

            // ── Temporal: disparar facturación automática al marcar como pagado ──
            if ($newPaymentStatus === Order::PAYMENT_STATUS_PAID && !$wasPaid) {
                $order->refresh();

                Log::info('[FACTURACION] ===== INICIO: pago marcado manualmente =====', [
                    'order_id'          => $order->id,
                    'order_number'      => $order->order_number,
                    'triggered_by'      => $user->id,
                    'triggered_by_role' => $user->roles->pluck('name')->implode(', '),
                    'total'             => $order->total,
                ]);

                event(new OrderPaymentConfirmed(
                    order: $order,
                    paymentMethod: 'manual',
                ));

                $invoiceCount = Invoice::where('order_id', $order->id)->count();
                Log::info('[FACTURACION] ===== FIN: evento emitido =====', [
                    'order_id'       => $order->id,
                    'orden_pagada'   => true,
                    'evento'         => 'OrderPaymentConfirmed',
                    'listener'       => 'GenerateInvoicesForOrder',
                    'invoices_creadas' => $invoiceCount,
                    'nubefact_llamado' => $invoiceCount > 0 ? 'SI' : 'NO (sin stores o error)',
                ]);
            }
            // ── Fin temporal ──
        }

        $order->load(['items.product.store', 'user']);

        broadcast(new OrderStatusChanged($order));

        return $this->success(new OrderResource($order));
    }

    public function confirmItem(Request $request, string $orderId, string $itemId): JsonResponse
    {
        $user = $request->user();

        if (! $user->hasRole('seller') && ! $user->hasRole('administrator')) {
            return $this->forbidden('Solo vendedores o administradores pueden confirmar items.');
        }

        $order = Order::with('items.product')->findOrFail($orderId);
        $item = $order->items()->findOrFail($itemId);

        if ($user->hasRole('seller')) {
            $storeIds = $user->stores()->pluck('stores.id')->toArray();
            if (! in_array($item->store_id, $storeIds)) {
                return $this->forbidden('Este item no pertenece a tu tienda.');
            }
        }

        if ($item->status !== OrderItem::STATUS_PENDING_SELLER) {
            return $this->error('Este item no puede ser confirmado en su estado actual.', 400);
        }

        $item->update(['status' => OrderItem::STATUS_CONFIRMED]);
        $order->refreshGlobalStatus();

        $order->load(['items.product.store', 'user']);

        return $this->success(new OrderResource($order));
    }

    public function requestReceipt(Request $request, string $id): JsonResponse
    {
        $user = $request->user();

        if (! $user->hasRole('customer')) {
            return $this->forbidden('Solo los clientes pueden solicitar comprobantes.');
        }

        $order = Order::with(['items.store.owner'])->findOrFail($id);

        if ($order->user_id !== $user->id) {
            return $this->forbidden('Esta orden no te pertenece.');
        }

        $firstItem = $order->items->first();

        if (! $firstItem || ! $firstItem->store) {
            return $this->error('No se encontró una tienda asociada a esta orden.', 404);
        }

        $storeId = $firstItem->store_id;
        $orderNumber = $order->order_number;

        // Buscar conversación existente de facturación con esta tienda
        $conversation = Conversation::where('customer_user_id', $user->id)
            ->where('store_id', $storeId)
            ->where('category', 'facturacion')
            ->where('status', 'active')
            ->first();

        if (! $conversation) {
            $conversation = Conversation::create([
                'customer_user_id' => $user->id,
                'store_id' => $storeId,
                'category' => 'facturacion',
                'subject' => "Solicitud de comprobante - Pedido {$orderNumber}",
                'last_message_at' => now(),
            ]);
        }

        $messageContent = "Hola, quisiera solicitar el comprobante de mi pedido {$orderNumber}.";

        $message = ConversationMessage::create([
            'conversation_id' => $conversation->id,
            'sender_id' => $user->id,
            'content' => $messageContent,
        ]);

        $conversation->update(['last_message_at' => now()]);

        broadcast(new NewConversationMessage(
            message: $message->loadMissing(['sender', 'conversation']),
            customerUserId: $user->id,
            storeId: (int) $storeId,
        ));

        $conversation->load(['store.owner', 'latestMessage']);

        return response()->json([
            'success' => true,
            'data' => new ConversationResource($conversation),
            'message' => 'Solicitud enviada al vendedor correctamente.',
        ]);
    }

    public function downloadReceipt(Request $request, string $id)
    {
        $user = $request->user();
        $order = Order::with(['items', 'user'])->findOrFail($id);

        if (! $user->hasRole('administrator') && $order->user_id !== $user->id) {
            return $this->forbidden('No tienes acceso a esta orden.');
        }

        $pdf = Pdf::loadView('pdf.receipt', ['order' => $order]);

        $filename = 'comprobante-' . $order->order_number . '.pdf';

        return $pdf->download($filename);
    }

    public function updateItemStatus(Request $request, string $orderId, string $itemId): JsonResponse
    {
        $data = $request->validate([
            'status' => ['required', 'string', 'in:processing,shipped,delivered,cancelled'],
        ]);

        $user = $request->user();
        $order = Order::with('items.product')->findOrFail($orderId);
        $item = $order->items()->findOrFail($itemId);

        $newStatus = $data['status'];

        if ($user->hasRole('seller')) {
            $storeIds = $user->stores()->pluck('stores.id')->toArray();
            if (! in_array($item->store_id, $storeIds)) {
                return $this->forbidden('Este item no pertenece a tu tienda.');
            }

            $validTransitions = [
                OrderItem::STATUS_CONFIRMED => OrderItem::STATUS_PROCESSING,
                OrderItem::STATUS_PROCESSING => OrderItem::STATUS_SHIPPED,
                OrderItem::STATUS_SHIPPED => OrderItem::STATUS_DELIVERED,
            ];

            if ($newStatus === OrderItem::STATUS_CANCELLED) {
                if (! in_array($item->status, [OrderItem::STATUS_PENDING_SELLER, OrderItem::STATUS_CONFIRMED])) {
                    return $this->error('Este item no puede ser cancelado en su estado actual.', 400);
                }

                $item->update(['status' => OrderItem::STATUS_CANCELLED]);
                $item->product->increment('stock', $item->quantity);
            } else {
                $expectedPreviousStatus = array_search($newStatus, $validTransitions);
                if ($item->status !== $expectedPreviousStatus) {
                    return $this->error("El item debe estar en estado '{$expectedPreviousStatus}' para cambiar a '{$newStatus}'.", 400);
                }

                $item->update(['status' => $newStatus]);
            }
        } elseif ($user->hasRole('administrator')) {
            if ($newStatus === OrderItem::STATUS_CANCELLED) {
                $item->update(['status' => OrderItem::STATUS_CANCELLED]);
                $item->product->increment('stock', $item->quantity);
            } else {
                $item->update(['status' => $newStatus]);
            }
        } else {
            if ($order->user_id !== $user->id) {
                return $this->forbidden('No tienes acceso a esta orden.');
            }

            if ($newStatus === OrderItem::STATUS_CANCELLED && $item->status === OrderItem::STATUS_PENDING_SELLER) {
                $item->update(['status' => OrderItem::STATUS_CANCELLED]);
                $item->product->increment('stock', $item->quantity);
            } else {
                return $this->forbidden('No tienes permiso para cambiar el estado de este item.');
            }
        }

        $order->refreshGlobalStatus();
        $order->load(['items.product.store', 'user']);

        return $this->success(new OrderResource($order));
    }

    public function resendNotification(Request $request, string $id): JsonResponse
    {
        $user = $request->user();

        $order = Order::with(['items.product.store', 'user'])->findOrFail($id);

        $memberStoreIds = $user->stores()->pluck('stores.id');
        $ownedStoreIds = $user->ownedStores()->pluck('stores.id');
        $storeIds = $memberStoreIds->merge($ownedStoreIds)->unique();
        $hasAccess = $order->items()->whereIn('store_id', $storeIds)->exists();

        if (! $hasAccess && ! $user->hasRole('administrator')) {
            return $this->forbidden('No tienes acceso a esta orden.');
        }

        $store = $user->ownedStores()->first() ?? $user->stores()->first();
        if (! $store) {
            return $this->forbidden('No tienes una tienda asociada.');
        }

        $user->notify(new NewOrderSellerNotification($order, $store));

        return $this->success(['message' => 'Notificación reenviada a tu correo.']);
    }
}
