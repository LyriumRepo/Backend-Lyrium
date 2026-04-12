<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\CalculateShippingRequest;
use App\Http\Requests\ConfigureStoreShippingRequest;
use App\Http\Requests\StoreShipmentRequest;
use App\Http\Requests\UpdateShipmentTrackingRequest;
use App\Http\Resources\ShipmentResource;
use App\Http\Resources\ShippingMethodResource;
use App\Http\Resources\ShippingZoneResource;
use App\Services\ShippingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class ShippingController extends Controller
{
    public function __construct(
        private readonly ShippingService $shippingService,
    ) {}

    public function methods(): JsonResponse
    {
        $methods = $this->shippingService->getAvailableMethods();

        return response()->json([
            'data' => ShippingMethodResource::collection($methods),
        ]);
    }

    public function zones(): JsonResponse
    {
        $zones = $this->shippingService->getAvailableZones();

        return response()->json([
            'data' => ShippingZoneResource::collection($zones),
        ]);
    }

    public function calculate(CalculateShippingRequest $request): JsonResponse
    {
        $results = $this->shippingService->calculateShipping(
            storeId: $request->input('store_id'),
            weight: (float) $request->input('weight'),
            orderTotal: (float) $request->input('order_total'),
            department: $request->input('department'),
            zoneId: $request->input('zone_id'),
        );

        return response()->json([
            'data' => $results,
        ]);
    }

    public function storeShipment(StoreShipmentRequest $request): JsonResponse
    {
        $user = $request->user();

        $shipment = $this->shippingService->createShipment(
            orderId: $request->input('order_id'),
            storeId: $user->stores()->first()?->id,
            methodId: $request->input('shipping_method_id'),
            orderItemId: $request->input('order_item_id'),
        );

        return response()->json(
            new ShipmentResource($shipment->load(['order', 'shippingMethod'])),
            201
        );
    }

    public function getOrderShipments(Request $request, int $orderId): JsonResponse
    {
        $shipments = $this->shippingService->getOrderShipments($orderId);

        return response()->json([
            'data' => ShipmentResource::collection($shipments),
        ]);
    }

    public function show(int $id): JsonResponse
    {
        $shipment = $this->shippingService->getShipment($id);

        return response()->json(new ShipmentResource($shipment));
    }

    public function updateTracking(UpdateShipmentTrackingRequest $request, int $id): JsonResponse
    {
        $shipment = $this->shippingService->updateTracking(
            shipmentId: $id,
            trackingNumber: $request->input('tracking_number'),
            carrier: $request->input('carrier'),
        );

        return response()->json(new ShipmentResource($shipment));
    }

    public function markShipped(Request $request, int $id): JsonResponse
    {
        $request->validate([
            'tracking_number' => ['nullable', 'string', 'max:100'],
            'carrier' => ['nullable', 'string', 'max:50'],
        ]);

        $shipment = $this->shippingService->markAsShipped(
            shipmentId: $id,
            trackingNumber: $request->input('tracking_number'),
            carrier: $request->input('carrier'),
        );

        return response()->json(new ShipmentResource($shipment));
    }

    public function markDelivered(int $id): JsonResponse
    {
        $shipment = $this->shippingService->markAsDelivered($id);

        return response()->json(new ShipmentResource($shipment));
    }

    public function updateStatus(Request $request, int $id): JsonResponse
    {
        $request->validate([
            'status' => ['required', 'string', 'in:pending,picked_up,in_transit,out_for_delivery,delivered,failed,returned'],
        ]);

        $shipment = $this->shippingService->updateStatus(
            shipmentId: $id,
            status: $request->input('status'),
        );

        return response()->json(new ShipmentResource($shipment));
    }

    public function addEvent(Request $request, int $id): JsonResponse
    {
        $request->validate([
            'event' => ['required', 'string', 'max:100'],
            'description' => ['nullable', 'string', 'max:500'],
        ]);

        $shipment = $this->shippingService->addEvent(
            shipmentId: $id,
            event: $request->input('event'),
            description: $request->input('description'),
        );

        return response()->json(new ShipmentResource($shipment));
    }

    public function sellerShipments(Request $request): JsonResponse
    {
        $user = $request->user();
        $store = $user->stores()->first();

        if (! $store) {
            return response()->json([
                'success' => false,
                'message' => 'No tienes una tienda',
            ], 403);
        }

        $shipments = $this->shippingService->getStoreShipments(
            storeId: $store->id,
            perPage: (int) $request->query('per_page', 15)
        );

        return response()->json([
            'data' => ShipmentResource::collection($shipments->items()),
            'meta' => [
                'current_page' => $shipments->currentPage(),
                'last_page' => $shipments->lastPage(),
                'per_page' => $shipments->perPage(),
                'total' => $shipments->total(),
            ],
        ]);
    }

    public function configureStore(ConfigureStoreShippingRequest $request): JsonResponse
    {
        $user = $request->user();
        $store = $user->stores()->first();

        if (! $store) {
            return response()->json([
                'success' => false,
                'message' => 'No tienes una tienda',
            ], 403);
        }

        $storeMethod = $this->shippingService->storeMethodForStore(
            storeId: $store->id,
            methodId: $request->input('method_id'),
            enabled: $request->boolean('is_enabled', true),
            additionalCost: (float) $request->input('additional_cost', 0),
            handlingDays: (int) $request->input('handling_time_days', 0),
        );

        return response()->json([
            'success' => true,
            'data' => [
                'store_id' => $storeMethod->store_id,
                'method_id' => $storeMethod->shipping_method_id,
                'is_enabled' => $storeMethod->is_enabled,
                'additional_cost' => (float) $storeMethod->additional_cost,
                'handling_time_days' => $storeMethod->handling_time_days,
            ],
        ]);
    }

    public function storeMethods(Request $request): JsonResponse
    {
        $user = $request->user();
        $store = $user->stores()->first();

        if (! $store) {
            return response()->json([
                'success' => false,
                'message' => 'No tienes una tienda',
            ], 403);
        }

        $methods = $this->shippingService->getMethodsForStore($store->id);

        return response()->json([
            'data' => $methods->map(fn ($m) => [
                'id' => $m->id,
                'code' => $m->code,
                'name' => $m->name,
                'type' => $m->type,
                'base_cost' => (float) $m->base_cost,
                'pivot_total_cost' => (float) ($m->pivot_total_cost ?? $m->base_cost),
                'pivot_handling_time' => $m->pivot_handling_time ?? 0,
            ]),
        ]);
    }
}
