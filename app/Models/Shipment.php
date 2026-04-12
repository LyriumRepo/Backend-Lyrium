<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class Shipment extends Model
{
    use HasFactory;

    public const STATUS_PENDING = 'pending';

    public const STATUS_PICKED_UP = 'picked_up';

    public const STATUS_IN_TRANSIT = 'in_transit';

    public const STATUS_OUT_FOR_DELIVERY = 'out_for_delivery';

    public const STATUS_DELIVERED = 'delivered';

    public const STATUS_FAILED = 'failed';

    public const STATUS_RETURNED = 'returned';

    protected $fillable = [
        'order_id',
        'order_item_id',
        'store_id',
        'shipping_method_id',
        'tracking_number',
        'tracking_url',
        'carrier',
        'status',
        'notes',
        'events',
        'shipped_at',
        'delivered_at',
    ];

    protected function casts(): array
    {
        return [
            'events' => 'array',
            'shipped_at' => 'datetime',
            'delivered_at' => 'datetime',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function orderItem(): BelongsTo
    {
        return $this->belongsTo(OrderItem::class, 'order_item_id');
    }

    public function store(): BelongsTo
    {
        return $this->belongsTo(Store::class);
    }

    public function shippingMethod(): BelongsTo
    {
        return $this->belongsTo(ShippingMethod::class);
    }

    public function isPending(): bool
    {
        return $this->status === self::STATUS_PENDING;
    }

    public function isInTransit(): bool
    {
        return in_array($this->status, [self::STATUS_PICKED_UP, self::STATUS_IN_TRANSIT, self::STATUS_OUT_FOR_DELIVERY]);
    }

    public function isDelivered(): bool
    {
        return $this->status === self::STATUS_DELIVERED;
    }

    public function canBeCancelled(): bool
    {
        return $this->status === self::STATUS_PENDING;
    }

    public function canBeShipped(): bool
    {
        return $this->status === self::STATUS_PENDING;
    }

    public function markAsShipped(?string $trackingNumber = null, ?string $carrier = null): void
    {
        $this->update([
            'status' => self::STATUS_PICKED_UP,
            'tracking_number' => $trackingNumber,
            'carrier' => $carrier,
            'shipped_at' => now(),
        ]);
    }

    public function markAsDelivered(): void
    {
        $this->update([
            'status' => self::STATUS_DELIVERED,
            'delivered_at' => now(),
        ]);
    }

    public function addEvent(string $event, ?string $description = null): void
    {
        $events = $this->events ?? [];
        $events[] = [
            'event' => $event,
            'description' => $description,
            'timestamp' => now()->toIso8601String(),
        ];
        $this->update(['events' => $events]);
    }

    public function scopeForStore($query, int $storeId)
    {
        return $query->where('store_id', $storeId);
    }

    public function scopeForOrder($query, int $orderId)
    {
        return $query->where('order_id', $orderId);
    }
}
