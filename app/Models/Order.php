<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\HasOne;


final class Order extends Model
{
    use HasFactory, SoftDeletes;

    public const STATUS_PENDING_SELLER = 'pending_seller';

    public const STATUS_CONFIRMED = 'confirmed';

    public const STATUS_PROCESSING = 'processing';

    public const STATUS_SHIPPED = 'shipped';

    public const STATUS_DELIVERED = 'delivered';

    public const STATUS_CANCELLED = 'cancelled';

    public const STATUSES = [
        self::STATUS_PENDING_SELLER,
        self::STATUS_CONFIRMED,
        self::STATUS_PROCESSING,
        self::STATUS_SHIPPED,
        self::STATUS_DELIVERED,
        self::STATUS_CANCELLED,
    ];

    public const ORDER_TYPE_PRODUCT = 'product';

    public const ORDER_TYPE_SERVICE = 'service';

    public const ORDER_TYPE_MIXED = 'mixed';

    public const PAYMENT_STATUS_PENDING = 'pending';

    public const PAYMENT_STATUS_PAID = 'paid';

    public const PAYMENT_STATUS_FAILED = 'failed';

    public const PAYMENT_STATUS_REFUNDED = 'refunded';

    public const MIN_ORDER_AMOUNT = 20.00;

    protected $fillable = [
        'order_number',
        'order_type',
        'user_id',
        'status',
        'payment_method',
        'payment_status',
        'shipping_name',
        'shipping_email',
        'shipping_phone',
        'shipping_address',
        'shipping_city',
        'shipping_postal_code',
        'shipping_notes',
        'shipping_type',
        'subtotal',
        'shipping_cost',
        'tax_amount',
        'discount_amount',
        'total',
        'notes',
        'coupon_code',
        'coupon_id',
    ];

    protected function casts(): array
    {
        return [
            'subtotal' => 'decimal:2',
            'shipping_cost' => 'decimal:2',
            'tax_amount' => 'decimal:2',
            'discount_amount' => 'decimal:2',
            'total' => 'decimal:2',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    public function shipments(): HasMany
    {
        return $this->hasMany(Shipment::class);
    }

    public function serviceItems(): HasMany
    {
        return $this->hasMany(OrderServiceItem::class);
    }

    public function coupon(): BelongsTo
    {
        return $this->belongsTo(Coupon::class);
    }

    public function getPaymentStatusLabelAttribute(): string
    {
        return match ($this->payment_status) {
            self::PAYMENT_STATUS_PENDING => 'Pendiente',
            self::PAYMENT_STATUS_PAID => 'Pagado',
            self::PAYMENT_STATUS_FAILED => 'Fallido',
            self::PAYMENT_STATUS_REFUNDED => 'Reembolsado',
            default => $this->payment_status,
        };
    }

    public static function generateOrderNumber(): string
    {
        $prefix = 'LYR';
        $timestamp = now()->format('YmdHis');
        $random = str_pad((string) random_int(0, 9999), 4, '0', STR_PAD_LEFT);

        return "{$prefix}-{$timestamp}-{$random}";
    }

    private function mapServiceStatus(string $serviceStatus): string
    {
        return match ($serviceStatus) {
            'pending' => self::STATUS_PENDING_SELLER,
            'confirmed' => self::STATUS_CONFIRMED,
            'completed' => self::STATUS_DELIVERED,
            'cancelled', 'no_show' => self::STATUS_CANCELLED,
            default => $serviceStatus,
        };
    }

    public function computeGlobalStatus(): string
    {
        $allStatuses = [];

        foreach ($this->items as $item) {
            $allStatuses[] = $item->status;
        }
        foreach ($this->serviceItems as $si) {
            $allStatuses[] = $this->mapServiceStatus($si->status);
        }

        if (empty($allStatuses)) {
            return $this->status;
        }

        $statuses = array_unique($allStatuses);

        if (count($statuses) === 1) {
            return $statuses[0];
        }

        if (in_array(self::STATUS_CANCELLED, $statuses)) {
            $nonCancelled = array_filter($allStatuses, fn($s) => $s !== self::STATUS_CANCELLED);
            if (empty($nonCancelled)) {
                return self::STATUS_CANCELLED;
            }
            $nonCancelledUnique = array_unique($nonCancelled);
            if (count($nonCancelledUnique) === 1) {
                return $nonCancelledUnique[0];
            }
        }

        if (in_array(self::STATUS_PENDING_SELLER, $statuses)) {
            return self::STATUS_PENDING_SELLER;
        }

        if (in_array(self::STATUS_PROCESSING, $statuses) || in_array(self::STATUS_SHIPPED, $statuses)) {
            return self::STATUS_PROCESSING;
        }

        if (in_array(self::STATUS_SHIPPED, $statuses)) {
            return self::STATUS_SHIPPED;
        }

        if (in_array(self::STATUS_DELIVERED, $statuses) && count($statuses) === 1) {
            return self::STATUS_DELIVERED;
        }

        return self::STATUS_CONFIRMED;
    }

    public function refreshGlobalStatus(): void
    {
        $newStatus = $this->computeGlobalStatus();
        if ($this->status !== $newStatus) {
            $this->update(['status' => $newStatus]);
        }
    }

    public function hasPendingSellerItems(): bool
    {
        return $this->items()->where('status', self::STATUS_PENDING_SELLER)->exists();
    }

    public function hasConfirmedItems(): bool
    {
        return $this->items()->where('status', self::STATUS_CONFIRMED)->exists();
    }

    public function getStoresInvolved(): array
    {
        return $this->items()->distinct()->pluck('store_id')->toArray();
    }

    public function getItemCountByStore(int $storeId): int
    {
        return $this->items()->where('store_id', $storeId)->count();
    }

    public function getConfirmedItemCountByStore(int $storeId): int
    {
        return $this->items()
            ->where('store_id', $storeId)
            ->where('status', '!=', self::STATUS_PENDING_SELLER)
            ->count();
    }

    public function isFullyConfirmed(): bool
    {
        return $this->items()->where('status', '!=', self::STATUS_PENDING_SELLER)->count() === $this->items()->count();
    }

    public function isFullyDelivered(): bool
    {
        return $this->items()->where('status', self::STATUS_DELIVERED)->count() === $this->items()->count();
    }

    public function isPendingSeller(): bool
    {
        return $this->status === self::STATUS_PENDING_SELLER;
    }

    public function isConfirmed(): bool
    {
        return $this->status === self::STATUS_CONFIRMED;
    }

    public function isProcessing(): bool
    {
        return $this->status === self::STATUS_PROCESSING;
    }

    public function isShipped(): bool
    {
        return $this->status === self::STATUS_SHIPPED;
    }

    public function isDelivered(): bool
    {
        return $this->status === self::STATUS_DELIVERED;
    }

    public function isCancelled(): bool
    {
        return $this->status === self::STATUS_CANCELLED;
    }

    public function canBeConfirmedBySeller(): bool
    {
        return $this->status === self::STATUS_PENDING_SELLER;
    }

    public function canSellerConfirmItems(int $storeId): bool
    {
        return $this->items()
            ->where('store_id', $storeId)
            ->where('status', self::STATUS_PENDING_SELLER)
            ->exists();
    }

    public function canSellerUpdateItems(int $storeId): bool
    {
        return $this->items()
            ->where('store_id', $storeId)
            ->whereNotIn('status', [self::STATUS_PENDING_SELLER, self::STATUS_CANCELLED])
            ->exists();
    }

    public function canBeUpdatedBySeller(): bool
    {
        return $this->status === self::STATUS_CONFIRMED
            || $this->status === self::STATUS_PROCESSING
            || $this->status === self::STATUS_SHIPPED;
    }

    public function getStatusLabel(): string
    {
        return match ($this->status) {
            self::STATUS_PENDING_SELLER => 'Esperando confirmación del vendedor',
            self::STATUS_CONFIRMED => 'Confirmado',
            self::STATUS_PROCESSING => 'Preparando pedido',
            self::STATUS_SHIPPED => 'Enviado',
            self::STATUS_DELIVERED => 'Entregado',
            self::STATUS_CANCELLED => 'Cancelado',
            default => $this->status,
        };
    }

    public function culqiTransactions(): HasMany
    {
        return $this->hasMany(CulqiTransaction::class);
    }
    public function latestCulqiTransaction()
    {
        return $this->hasOne(CulqiTransaction::class)->latestOfMany();
    }

    public function izipayTransactions(): HasMany
    {
        return $this->hasMany(IzipayOrderTransaction::class);
    }

    public function latestIzipayTransaction(): HasOne
    {
        return $this->hasOne(IzipayOrderTransaction::class)->latestOfMany();
    }
}
