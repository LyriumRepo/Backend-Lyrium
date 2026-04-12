<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

final class Dispute extends Model
{
    use HasFactory, SoftDeletes;

    public const STATUS_OPEN = 'open';

    public const STATUS_UNDER_REVIEW = 'under_review';

    public const STATUS_PENDING_RESOLUTION = 'pending_resolution';

    public const STATUS_RESOLVED = 'resolved';

    public const STATUS_CLOSED = 'closed';

    public const STATUS_CANCELLED = 'cancelled';

    public const STATUSES = [
        self::STATUS_OPEN,
        self::STATUS_UNDER_REVIEW,
        self::STATUS_PENDING_RESOLUTION,
        self::STATUS_RESOLVED,
        self::STATUS_CLOSED,
        self::STATUS_CANCELLED,
    ];

    public const PRIORITY_LOW = 'low';

    public const PRIORITY_MEDIUM = 'medium';

    public const PRIORITY_HIGH = 'high';

    public const PRIORITY_URGENT = 'urgent';

    public const PRIORITIES = [
        self::PRIORITY_LOW,
        self::PRIORITY_MEDIUM,
        self::PRIORITY_HIGH,
        self::PRIORITY_URGENT,
    ];

    public const TYPE_PRODUCT_NOT_RECEIVED = 'product_not_received';

    public const TYPE_PRODUCT_DAMAGED = 'product_damaged';

    public const TYPE_PRODUCT_NOT_AS_DESCRIBED = 'product_not_as_described';

    public const TYPE_SELLER_FRAUD = 'seller_fraud';

    public const TYPE_PAYMENT_ISSUE = 'payment_issue';

    public const TYPE_OTHER = 'other';

    public const TYPES = [
        self::TYPE_PRODUCT_NOT_RECEIVED,
        self::TYPE_PRODUCT_DAMAGED,
        self::TYPE_PRODUCT_NOT_AS_DESCRIBED,
        self::TYPE_SELLER_FRAUD,
        self::TYPE_PAYMENT_ISSUE,
        self::TYPE_OTHER,
    ];

    public const RESOLUTION_FAVOR_BUYER = 'favor_buyer';

    public const RESOLUTION_FAVOR_SELLER = 'favor_seller';

    public const RESOLUTION_PARTIAL_REFUND = 'partial_refund';

    public const RESOLUTION_OTHER = 'other';

    public const RESOLUTIONS = [
        self::RESOLUTION_FAVOR_BUYER,
        self::RESOLUTION_FAVOR_SELLER,
        self::RESOLUTION_PARTIAL_REFUND,
        self::RESOLUTION_OTHER,
    ];

    protected $fillable = [
        'order_id',
        'user_id',
        'store_id',
        'dispute_number',
        'type',
        'status',
        'priority',
        'description',
        'resolution_notes',
        'resolution',
        'refund_amount',
        'assigned_to',
        'opened_at',
        'reviewed_at',
        'resolved_at',
        'closed_at',
    ];

    protected function casts(): array
    {
        return [
            'refund_amount' => 'decimal:2',
            'opened_at' => 'datetime',
            'reviewed_at' => 'datetime',
            'resolved_at' => 'datetime',
            'closed_at' => 'datetime',
        ];
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function store(): BelongsTo
    {
        return $this->belongsTo(Store::class);
    }

    public function assignedTo(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    public function messages(): HasMany
    {
        return $this->hasMany(DisputeMessage::class)->orderBy('created_at', 'asc');
    }

    public function attachments(): HasMany
    {
        return $this->hasMany(DisputeAttachment::class);
    }

    public static function generateDisputeNumber(): string
    {
        $prefix = 'DSP';
        $timestamp = now()->format('YmdHis');
        $random = str_pad((string) random_int(0, 9999), 4, '0', STR_PAD_LEFT);

        return "{$prefix}-{$timestamp}-{$random}";
    }

    public function canBeClosed(): bool
    {
        return in_array($this->status, [self::STATUS_RESOLVED, self::STATUS_CANCELLED]);
    }

    public function canAddMessage(): bool
    {
        return ! in_array($this->status, [self::STATUS_CLOSED, self::STATUS_CANCELLED]);
    }

    public function isOpen(): bool
    {
        return $this->status === self::STATUS_OPEN;
    }

    public function isResolved(): bool
    {
        return $this->status === self::STATUS_RESOLVED;
    }

    public function isClosed(): bool
    {
        return $this->status === self::STATUS_CLOSED;
    }

    public function getStatusLabel(): string
    {
        return match ($this->status) {
            self::STATUS_OPEN => 'Abierta',
            self::STATUS_UNDER_REVIEW => 'En revisión',
            self::STATUS_PENDING_RESOLUTION => 'Pendiente de resolución',
            self::STATUS_RESOLVED => 'Resuelta',
            self::STATUS_CLOSED => 'Cerrada',
            self::STATUS_CANCELLED => 'Cancelada',
            default => $this->status,
        };
    }

    public function getTypeLabel(): string
    {
        return match ($this->type) {
            self::TYPE_PRODUCT_NOT_RECEIVED => 'Producto no recibido',
            self::TYPE_PRODUCT_DAMAGED => 'Producto dañado',
            self::TYPE_PRODUCT_NOT_AS_DESCRIBED => 'Producto no como se describió',
            self::TYPE_SELLER_FRAUD => 'Fraude del vendedor',
            self::TYPE_PAYMENT_ISSUE => 'Problema de pago',
            self::TYPE_OTHER => 'Otro',
            default => $this->type,
        };
    }
}
