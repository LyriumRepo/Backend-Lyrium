<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\ProductReturn;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use App\Services\AuditService;

final class ReturnService
{
    private const DEFAULT_PER_PAGE = 15;

    private const MAX_PER_PAGE = 100;

    public function create(array $data): ProductReturn
    {
        $return = ProductReturn::create([
            'order_id' => $data['order_id'],
            'user_id' => $data['user_id'],
            'store_id' => $data['store_id'],
            'return_number' => ProductReturn::generateReturnNumber(),
            'status' => ProductReturn::STATUS_PENDING,
            'reason' => $data['reason'],
            'reason_details' => $data['reason_details'] ?? null,
            'refund_method' => $data['refund_method'] ?? 'original_payment',
            'requested_at' => now(),
        ]);

        foreach ($data['items'] as $item) {
            $return->items()->create([
                'order_item_id' => $item['order_item_id'],
                'quantity' => $item['quantity'] ?? 1,
                'notes' => $item['notes'] ?? null,
            ]);
        }

        app(AuditService::class)->record(
            event: 'returns.created',
            module: 'returns',
            description: "Devolución #{$return->return_number} creada para la orden #{$return->order_id}",
            auditable: $return,
            newValues: ['status' => ProductReturn::STATUS_PENDING],
            source: AuditService::SOURCE_WEB,
            metadata: [
                'return_id' => $return->id,
                'order_id' => $return->order_id,
            ],
        );

        return $return->fresh(['items', 'order', 'store']);
    }

    public function findOrFail(int $id): ProductReturn
    {
        return ProductReturn::query()
            ->with(['items.orderItem', 'order', 'store', 'user'])
            ->findOrFail($id);
    }

    public function approve(int $id, ?string $notes = null, ?float $refundAmount = null): ProductReturn
    {
        $return = $this->findOrFail($id);

        if (! $return->canApprove()) {
            throw new \InvalidArgumentException('Esta devolución no puede ser aprobada');
        }

        $oldStatus = $return->status;

        $return->update([
            'status' => ProductReturn::STATUS_APPROVED,
            'resolution_notes' => $notes,
            'refund_amount' => $refundAmount,
            'reviewed_at' => now(),
        ]);

        app(AuditService::class)->record(
            event: 'returns.approved',
            module: 'returns',
            description: "Devolución #{$return->return_number} aprobada",
            auditable: $return,
            oldValues: ['status' => $oldStatus],
            newValues: ['status' => ProductReturn::STATUS_APPROVED],
            source: AuditService::SOURCE_WEB,
            metadata: [
                'return_id' => $return->id,
                'order_id' => $return->order_id,
            ],
        );

        return $return->fresh();
    }

    public function reject(int $id, string $reason): ProductReturn
    {
        $return = $this->findOrFail($id);

        if (! $return->canReject()) {
            throw new \InvalidArgumentException('Esta devolución no puede ser rechazada');
        }

        $oldStatus = $return->status;

        $return->update([
            'status' => ProductReturn::STATUS_REJECTED,
            'resolution_notes' => $reason,
            'reviewed_at' => now(),
        ]);

        app(AuditService::class)->record(
            event: 'returns.rejected',
            module: 'returns',
            description: "Devolución #{$return->return_number} rechazada",
            auditable: $return,
            oldValues: ['status' => $oldStatus],
            newValues: ['status' => ProductReturn::STATUS_REJECTED],
            source: AuditService::SOURCE_WEB,
            metadata: [
                'return_id' => $return->id,
                'order_id' => $return->order_id,
            ],
        );

        return $return->fresh();
    }

    public function markReceived(int $id): ProductReturn
    {
        $return = $this->findOrFail($id);

        if (! $return->canMarkReceived()) {
            throw new \InvalidArgumentException('Esta devolución no puede ser marcada como recibida');
        }

        $oldStatus = $return->status;

        $return->update([
            'status' => ProductReturn::STATUS_RECEIVED,
        ]);

        app(AuditService::class)->record(
            event: 'returns.received',
            module: 'returns',
            description: "Devolución #{$return->return_number} recibida",
            auditable: $return,
            oldValues: ['status' => $oldStatus],
            newValues: ['status' => ProductReturn::STATUS_RECEIVED],
            source: AuditService::SOURCE_WEB,
            metadata: [
                'return_id' => $return->id,
                'order_id' => $return->order_id,
            ],
        );

        return $return->fresh();
    }

    public function refund(int $id): ProductReturn
    {
        $return = $this->findOrFail($id);

        if (! $return->canRefund()) {
            throw new \InvalidArgumentException('Esta devolución no puede ser reembolsada');
        }

        $oldStatus = $return->status;

        $return->update([
            'status' => ProductReturn::STATUS_REFUNDED,
            'resolved_at' => now(),
        ]);

        app(AuditService::class)->record(
            event: 'returns.refunded',
            module: 'returns',
            description: "Devolución #{$return->return_number} reembolsada",
            auditable: $return,
            oldValues: ['status' => $oldStatus],
            newValues: ['status' => ProductReturn::STATUS_REFUNDED],
            source: AuditService::SOURCE_WEB,
            metadata: [
                'return_id' => $return->id,
                'order_id' => $return->order_id,
            ],
        );

        return $return->fresh();
    }

    public function cancel(int $id): ProductReturn
    {
        $return = $this->findOrFail($id);

        if (! $return->canCancel()) {
            throw new \InvalidArgumentException('Esta devolución no puede ser cancelada');
        }

        $return->update([
            'status' => ProductReturn::STATUS_CANCELLED,
        ]);

        return $return->fresh();
    }

    public function updateTracking(int $id, string $carrier, string $trackingNumber): ProductReturn
    {
        $return = $this->findOrFail($id);

        $return->update([
            'shipping_carrier' => $carrier,
            'tracking_number' => $trackingNumber,
        ]);

        return $return->fresh();
    }

    public function getUserReturns(int $userId, int $perPage = self::DEFAULT_PER_PAGE): LengthAwarePaginator
    {
        $perPage = min($perPage, self::MAX_PER_PAGE);

        return ProductReturn::query()
            ->where('user_id', $userId)
            ->with(['items.orderItem', 'store'])
            ->latest()
            ->paginate($perPage);
    }

    public function getStoreReturns(int $storeId, int $perPage = self::DEFAULT_PER_PAGE): LengthAwarePaginator
    {
        $perPage = min($perPage, self::MAX_PER_PAGE);

        return ProductReturn::query()
            ->where('store_id', $storeId)
            ->with(['items.orderItem', 'order', 'user'])
            ->latest()
            ->paginate($perPage);
    }

    public function getOrderReturns(int $orderId): Collection
    {
        return ProductReturn::query()
            ->where('order_id', $orderId)
            ->with(['items.orderItem', 'store'])
            ->get();
    }
}
