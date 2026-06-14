<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Service — Modelo actualizado.
 *
 * CAMBIOS respecto a la versión anterior:
 *  - specialists() → BelongsToMany con withTrashed() para evitar que
 *    SoftDeletes oculte especialistas activos por un bug de eager load.
 *  - $fillable actualizado con todos los campos nuevos.
 */
final class Service extends Model
{
    use HasFactory;
    use SoftDeletes;

    public const STATUS_ACTIVE = 'active';

    public const STATUS_INACTIVE = 'inactive';

    public const STATUS_DRAFT = 'draft';

    public const STATUS_PENDING_REVIEW = 'pending_review';

    public const STATUS_APPROVED = 'approved';

    public const STATUS_REJECTED = 'rejected';

    public const CANCELLATION_NO_REFUND = 'no_refund';

    public const CANCELLATION_FLEXIBLE = 'flexible';

    public const CANCELLATION_STRICT = 'strict';

    protected $fillable = [
        'store_id',
        'category_id',
        'name',
        'slug',
        'description',
        'benefits',
        'image',
        'price',
        'duration_minutes',
        'buffer_minutes',
        'is_home_service',
        'booking_advance_hours',
        'max_capacity',
        'status',
        'rejection_reason',
        'reviewed_at',
        'reviewed_by',
        'cancellation_policy',
        'max_cancellations',
        'settings',
        'google_calendar_id',
        'sticker',
        'discount_percentage',
    ];

    protected function casts(): array
    {
        return [
            'price' => 'decimal:2',
            'duration_minutes' => 'integer',
            'buffer_minutes' => 'integer',
            'is_home_service' => 'boolean',
            'max_capacity' => 'integer',
            'settings' => 'array',
            'discount_percentage' => 'decimal:2',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
            'deleted_at' => 'datetime',
        ];
    }

    // ── Relaciones ────────────────────────────────────────────────────────────

    /**
     * Especialistas asignados a este servicio (pivot service_specialist).
     *
     * IMPORTANTE: Se usa withTrashed() para que SoftDeletes del modelo
     * Specialist no oculte especialistas válidos cuando Eloquent hace
     * el eager load del pivot. Los especialistas borrados se filtran
     * después en el Resource si se requiere.
     */
    public function specialists(): BelongsToMany
    {
        return $this->belongsToMany(Specialist::class, 'service_specialist')
            ->withTrashed()
            ->withTimestamps();
    }

    public function store(): BelongsTo
    {
        return $this->belongsTo(Store::class);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function schedules(): HasMany
    {
        return $this->hasMany(ServiceSchedule::class);
    }

    public function bookings(): HasMany
    {
        return $this->hasMany(ServiceBooking::class);
    }

    public function reviews(): HasManyThrough
    {
        return $this->hasManyThrough(
            Review::class,
            ServiceBooking::class,
            'service_id',
            'service_booking_id',
            'id',
            'id',
        );
    }

    // ── Helpers ───────────────────────────────────────────────────────────────

    public function isActive(): bool
    {
        return $this->status === self::STATUS_ACTIVE;
    }

    public function canCancelWithoutRefund(): bool
    {
        return $this->cancellation_policy === self::CANCELLATION_NO_REFUND;
    }

    public function isFlexibleCancellation(): bool
    {
        return $this->cancellation_policy === self::CANCELLATION_FLEXIBLE;
    }

    public function finalPrice(): float
    {
        $price = (float) $this->price;
        $discount = (float) ($this->discount_percentage ?? 0);

        return $discount > 0 ? round($price * (1 - $discount / 100), 2) : $price;
    }

    public function scopeActive($query)
    {
        return $query->where('status', self::STATUS_ACTIVE);
    }
}
