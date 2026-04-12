<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Shipment;
use App\Models\ShippingMethod;
use App\Models\ShippingRate;
use App\Models\ShippingZone;
use App\Models\StoreShippingMethod;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

final class ShippingService
{
    private const DEFAULT_PER_PAGE = 15;

    private const MAX_PER_PAGE = 100;

    public function getMethodsForStore(int $storeId, ?string $department = null): Collection
    {
        $storeMethods = StoreShippingMethod::where('store_id', $storeId)
            ->where('is_enabled', true)
            ->with(['method' => fn ($q) => $q->active()])
            ->get();

        return $storeMethods->map(function ($storeMethod) {
            $method = $storeMethod->method;
            $method->pivot_total_cost = $storeMethod->getTotalCost($method->base_cost);
            $method->pivot_handling_time = $storeMethod->handling_time_days;

            return $method;
        })->filter(fn ($m) => $m !== null);
    }

    public function calculateShipping(int $storeId, float $weight, float $orderTotal, string $department, ?int $zoneId = null): array
    {
        $methods = $this->getMethodsForStore($storeId, $department);
        $zone = $zoneId
            ? ShippingZone::find($zoneId)
            : ShippingZone::active()->where('department', $department)->first();

        $results = [];

        foreach ($methods as $method) {
            $rate = null;

            if ($zone) {
                $rate = ShippingRate::active()
                    ->where('shipping_method_id', $method->id)
                    ->where('zone_id', $zone->id)
                    ->where('weight_from', '<=', $weight)
                    ->where(fn ($q) => $q->whereNull('weight_to')->orWhere('weight_to', '>=', $weight))
                    ->first();
            }

            $basePrice = $rate ? (float) $rate->price : (float) $method->base_cost;
            $totalCost = $method->pivot_total_cost ?? $basePrice;

            if ($method->isFreeShippingEligible($orderTotal)) {
                $totalCost = 0;
            }

            $results[] = [
                'method_id' => $method->id,
                'code' => $method->code,
                'name' => $method->name,
                'type' => $method->type,
                'price' => $totalCost,
                'estimated_days' => $rate?->estimated_days ?? $method->estimated_days,
                'allows_tracking' => $method->allows_tracking,
                'handling_time' => $method->pivot_handling_time ?? 0,
            ];
        }

        usort($results, fn ($a, $b) => $a['price'] <=> $b['price']);

        return $results;
    }

    public function createShipment(int $orderId, int $storeId, int $methodId, ?int $orderItemId = null): Shipment
    {
        return Shipment::create([
            'order_id' => $orderId,
            'order_item_id' => $orderItemId,
            'store_id' => $storeId,
            'shipping_method_id' => $methodId,
            'status' => Shipment::STATUS_PENDING,
        ]);
    }

    public function updateTracking(int $shipmentId, string $trackingNumber, ?string $carrier = null): Shipment
    {
        $shipment = Shipment::findOrFail($shipmentId);

        $trackingUrl = null;
        if ($carrier) {
            $trackingUrl = $this->generateTrackingUrl($carrier, $trackingNumber);
        }

        $shipment->update([
            'tracking_number' => $trackingNumber,
            'carrier' => $carrier,
            'tracking_url' => $trackingUrl,
        ]);

        return $shipment->fresh();
    }

    public function markAsShipped(int $shipmentId, ?string $trackingNumber = null, ?string $carrier = null): Shipment
    {
        $shipment = Shipment::findOrFail($shipmentId);

        if (! $shipment->canBeShipped()) {
            throw new \InvalidArgumentException('Este envío no puede ser marcado como enviado');
        }

        $shipment->markAsShipped($trackingNumber, $carrier);

        return $shipment->fresh();
    }

    public function markAsDelivered(int $shipmentId): Shipment
    {
        $shipment = Shipment::findOrFail($shipmentId);

        if (! $shipment->isInTransit()) {
            throw new \InvalidArgumentException('Este envío no puede ser marcado como entregado');
        }

        $shipment->markAsDelivered();

        return $shipment->fresh();
    }

    public function updateStatus(int $shipmentId, string $status): Shipment
    {
        $shipment = Shipment::findOrFail($shipmentId);

        $shipment->update(['status' => $status]);

        return $shipment->fresh();
    }

    public function getStoreShipments(int $storeId, int $perPage = self::DEFAULT_PER_PAGE): LengthAwarePaginator
    {
        $perPage = min($perPage, self::MAX_PER_PAGE);

        return Shipment::query()
            ->where('store_id', $storeId)
            ->with(['order', 'orderItem', 'shippingMethod'])
            ->latest()
            ->paginate($perPage);
    }

    public function getOrderShipments(int $orderId): Collection
    {
        return Shipment::query()
            ->where('order_id', $orderId)
            ->with(['store', 'shippingMethod'])
            ->get();
    }

    public function getShipment(int $id): Shipment
    {
        return Shipment::query()
            ->with(['order', 'orderItem', 'store', 'shippingMethod'])
            ->findOrFail($id);
    }

    public function addEvent(int $shipmentId, string $event, ?string $description = null): Shipment
    {
        $shipment = Shipment::findOrFail($shipmentId);
        $shipment->addEvent($event, $description);

        return $shipment->fresh();
    }

    private function generateTrackingUrl(string $carrier, string $trackingNumber): ?string
    {
        return match (strtolower($carrier)) {
            'dhl' => "https://www.dhl.com/pe-es/tracking?AWB={$trackingNumber}",
            'fedex' => "https://www.fedex.com/fedextrack/?trknbr={$trackingNumber}",
            'ups' => "https://www.ups.com/track?tracknum={$trackingNumber}",
            'peru_post' => "https://www.perupost.com.pe/track?tracking={$trackingNumber}",
            'olva' => "https://www.olvacourier.com/track?codigo={$trackingNumber}",
            default => null,
        };
    }

    public function storeMethodForStore(int $storeId, int $methodId, bool $enabled = true, float $additionalCost = 0, int $handlingDays = 0): StoreShippingMethod
    {
        return StoreShippingMethod::updateOrCreate(
            ['store_id' => $storeId, 'shipping_method_id' => $methodId],
            [
                'is_enabled' => $enabled,
                'additional_cost' => $additionalCost,
                'handling_time_days' => $handlingDays,
            ]
        );
    }

    public function getAvailableZones(): Collection
    {
        return ShippingZone::active()->get(['id', 'name', 'country', 'region', 'department']);
    }

    public function getAvailableMethods(): Collection
    {
        return ShippingMethod::active()->get();
    }
}
