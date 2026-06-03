<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\ServiceBooking;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class AgendaController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();

        $storeIds = $user->ownedStores()->pluck('id')
            ->concat($user->stores()->pluck('stores.id'))
            ->unique();

        if ($storeIds->isEmpty()) {
            return $this->success([
                'data' => [],
                'meta' => [
                    'current_page' => 1,
                    'last_page' => 1,
                    'per_page' => 15,
                    'total' => 0,
                ],
            ]);
        }

        $month = (int) $request->query('month', (int) date('n'));
        $year = (int) $request->query('year', (int) date('Y'));
        $type = $request->query('type', 'all');
        $perPage = (int) $request->query('per_page', 100);
        $dateFilter = $request->query('date');

        $events = collect();

        if (in_array($type, ['all', 'orders'], true)) {
            $ordersQuery = Order::with([
                'user',
                'items' => fn ($q) => $q->with('product'),
                'shipments',
                'latestIzipayTransaction',
                'latestCulqiTransaction',
            ])
                ->where('payment_status', Order::PAYMENT_STATUS_PAID)
                ->where(function ($q) use ($storeIds) {
                    $q->whereHas('items', fn ($q) => $q->whereIn('store_id', $storeIds));
                });

            if ($dateFilter) {
                $ordersQuery->whereDate('created_at', $dateFilter);
            } else {
                $ordersQuery->whereYear('created_at', $year)
                    ->whereMonth('created_at', $month);
            }

            $orders = $ordersQuery->orderBy('created_at')->get();

            foreach ($orders as $order) {
                $shipment = $order->shipments->first();

                $itemsData = $order->items->map(function ($item) {
                    $imageUrl = null;
                    if ($item->product && method_exists($item->product, 'getFirstMediaUrl')) {
                        $imageUrl = $item->product->getFirstMediaUrl('images', 'thumb');
                    }

                    return [
                        'product_name' => $item->product_name,
                        'quantity' => $item->quantity,
                        'unit_price' => (float) $item->unit_price,
                        'line_total' => (float) $item->line_total,
                        'image_url' => $imageUrl,
                    ];
                })->toArray();

                $paidAt = null;
                if ($order->relationLoaded('latestCulqiTransaction') && $order->latestCulqiTransaction && $order->latestCulqiTransaction->isPaid()) {
                    $paidAt = $order->latestCulqiTransaction->updated_at?->toIso8601String();
                } elseif ($order->relationLoaded('latestIzipayTransaction') && $order->latestIzipayTransaction && $order->latestIzipayTransaction->isPaid()) {
                    $paidAt = $order->latestIzipayTransaction->updated_at?->toIso8601String();
                } else {
                    $paidAt = $order->updated_at?->toIso8601String();
                }

                $events->push([
                    'id' => 'order_' . $order->id,
                    'type' => 'order',
                    'date' => $order->created_at->toDateString(),
                    'time' => $order->created_at->format('H:i'),
                    'title' => $order->order_number,
                    'subtitle' => $order->user?->name ?? 'Cliente',
                    'status' => $order->payment_status,
                    'payment_status' => $order->payment_status,
                    'total' => (float) $order->total,
                    'customer_name' => $order->user?->name ?? 'Cliente',
                    'customer_email' => $order->user?->email ?? '',
                    'customer_phone' => $order->user?->phone ?? '',
                    'items_summary' => $this->getOrderSummary($order),
                    'order_id' => $order->id,
                    'order_number' => $order->order_number,
                    'order_status' => $order->status,
                    'payment_method' => $order->payment_method,
                    'paid_at' => $paidAt,
                    'shipping_address' => $order->shipping_address,
                    'shipping_city' => $order->shipping_city,
                    'shipping_type' => $order->shipping_type,
                    'shipping_notes' => $order->shipping_notes,
                    'subtotal' => (float) $order->subtotal,
                    'shipping_cost' => (float) $order->shipping_cost,
                    'tax_amount' => (float) $order->tax_amount,
                    'discount_amount' => (float) $order->discount_amount,
                    'items' => $itemsData,
                    'shipment' => $shipment ? [
                        'carrier' => $shipment->carrier,
                        'tracking_number' => $shipment->tracking_number,
                        'tracking_url' => $shipment->tracking_url,
                        'status' => $shipment->status,
                        'shipped_at' => $shipment->shipped_at?->toIso8601String(),
                        'delivered_at' => $shipment->delivered_at?->toIso8601String(),
                    ] : null,
                ]);
            }
        }

        if (in_array($type, ['all', 'services'], true)) {
            $bookingsQuery = ServiceBooking::with([
                'service' => fn ($q) => $q->with('store'),
                'user',
                'specialist',
                'schedule',
            ])
                ->where('payment_status', 'paid')
                ->whereHas('service', fn ($q) => $q->whereIn('store_id', $storeIds));

            if ($dateFilter) {
                $bookingsQuery->whereDate('appointment_date', $dateFilter);
            } else {
                $bookingsQuery->whereYear('appointment_date', $year)
                    ->whereMonth('appointment_date', $month);
            }

            $bookings = $bookingsQuery->orderBy('appointment_date')->get();

            foreach ($bookings as $booking) {
                $serviceName = $booking->service?->name ?? 'Servicio';

                $events->push([
                    'id' => 'booking_' . $booking->id,
                    'type' => 'service',
                    'date' => $booking->appointment_date->toDateString(),
                    'time' => $booking->appointment_date->format('H:i'),
                    'title' => $serviceName,
                    'subtitle' => $booking->user?->name ?? 'Cliente',
                    'status' => $booking->status,
                    'payment_status' => $booking->payment_status,
                    'total' => (float) $booking->total_price,
                    'customer_name' => $booking->user?->name ?? 'Cliente',
                    'customer_email' => $booking->user?->email ?? '',
                    'customer_phone' => $booking->user?->phone ?? '',
                    'service_name' => $serviceName,
                    'booking_id' => $booking->id,
                    'service_id' => $booking->service_id,
                    'duration_minutes' => $booking->service?->duration_minutes,
                    'payment_method' => $booking->payment_method,
                    'notes' => $booking->customer_notes,
                    'seller_notes' => $booking->seller_notes,
                    'appointment_date' => $booking->appointment_date->toIso8601String(),
                    'end_time' => $booking->appointment_date?->copy()->addMinutes($booking->service?->duration_minutes ?? 60)->format('H:i'),
                    'specialist' => $booking->specialist ? [
                        'id' => $booking->specialist->id,
                        'nombres' => $booking->specialist->nombres,
                        'apellidos' => $booking->specialist->apellidos,
                        'especialidad' => $booking->specialist->especialidad,
                        'foto' => $booking->specialist->foto,
                    ] : null,
                ]);
            }
        }

        $sorted = $events->sortBy(['date', 'time'])->values();

        $total = $sorted->count();
        $currentPage = max(1, (int) $request->query('page', 1));
        $lastPage = max(1, (int) ceil($total / max(1, $perPage)));
        $offset = ($currentPage - 1) * $perPage;
        $items = $sorted->slice($offset, $perPage)->values();

        return $this->success([
            'data' => $items,
            'meta' => [
                'current_page' => $currentPage,
                'last_page' => $lastPage,
                'per_page' => $perPage,
                'total' => $total,
            ],
        ]);
    }

    private function getOrderSummary(Order $order): string
    {
        if ($order->relationLoaded('items') && $order->items->isNotEmpty()) {
            $firstName = $order->items->first()->product_name ?? '';
            $count = $order->items->count();

            return $count <= 1 ? $firstName : $firstName . ' + ' . ($count - 1) . ' más';
        }

        return '';
    }
}
