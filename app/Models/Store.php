<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

final class Store extends Model implements HasMedia
{
    use HasFactory, InteractsWithMedia, SoftDeletes;

    protected $fillable = [
        'owner_id',
        'ruc',
        'trade_name',
        'razon_social',
        'nombre_comercial',
        'corporate_email',
        'slug',
        'description',
        'activity',
        'logo',
        'banner',
        'banner2',
        'store_name',
        'category_id',
        'address',
        'phone',
        'status',
        'seller_type',
        'strikes',
        'commission_rate',
        'lirios_percent',
        'rep_legal_nombre',
        'rep_legal_dni',
        'rep_legal_foto',
        'experience_years',
        'tax_condition',
        'direccion_fiscal',
        'cuenta_bcp',
        'cci',
        'bank_secondary',
        'instagram',
        'facebook',
        'tiktok',
        'whatsapp',
        'youtube',
        'twitter',
        'linkedin',
        'website',
        'policies',
        'gallery',
        'layout',
        'profile_status',
        'profile_updated_at',
        'google_calendar_id',
        'google_calendar_token',
        'approved_at',
        'banned_at',
        'sla_notified_at',
    ];

    protected function casts(): array
    {
        return [
            'strikes' => 'integer',
            'commission_rate' => 'decimal:4',
            'experience_years' => 'integer',
            'approved_at' => 'datetime',
            'banned_at' => 'datetime',
            'sla_notified_at' => 'datetime',
            'gallery' => 'array',
            'bank_secondary' => 'array',
            'profile_status' => 'string',
            'profile_updated_at' => 'datetime',
        ];
    }

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function getStoreNameAttribute(): ?string
    {
        return $this->attributes['store_name'] ?? $this->trade_name;
    }

    public function members(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'store_members')
            ->withPivot('role')
            ->withTimestamps();
    }

    public function products(): HasMany
    {
        return $this->hasMany(Product::class);
    }

    public function subscription(): HasOne
    {
        return $this->hasOne(Subscription::class)->latestOfMany();
    }

    public function subscriptions(): HasMany
    {
        return $this->hasMany(Subscription::class);
    }

    public function branches(): HasMany
    {
        return $this->hasMany(StoreBranch::class);
    }

    public function suppliers(): BelongsToMany
    {
        return $this->belongsToMany(Supplier::class, 'supplier_store')->withTimestamps();
    }

    public function contracts(): HasMany
    {
        return $this->hasMany(Contract::class);
    }

    public function activeContract(): HasOne
    {
        return $this->hasOne(Contract::class)->where('status', 'ACTIVE')->latestOfMany();
    }

    public function isApproved(): bool
    {
        return $this->status === 'approved';
    }

    public function isBanned(): bool
    {
        return $this->status === 'banned';
    }

    public function isProfileComplete(): bool
    {
        return filled($this->razon_social)
            && filled($this->rep_legal_nombre)
            && filled($this->rep_legal_dni)
            && filled($this->direccion_fiscal);
    }

    public function missingProfileFields(): array
    {
        $missing = [];

        if (! filled($this->razon_social)) {
            $missing[] = 'Razon social';
        }

        if (! filled($this->rep_legal_nombre)) {
            $missing[] = 'Nombre del representante legal';
        }

        if (! filled($this->rep_legal_dni)) {
            $missing[] = 'DNI del representante legal';
        }

        if (! filled($this->direccion_fiscal)) {
            $missing[] = 'Direccion fiscal';
        }

        return $missing;
    }

    public function addStrike(): void
    {
        $this->increment('strikes');
        if ($this->strikes >= 3) {
            $this->update([
                'status' => 'banned',
                'banned_at' => now(),
            ]);
        }
    }

    public function getPolicyUrl(string $type): ?string
    {
        $media = $this->media()
            ->where('collection_name', 'policies')
            ->whereJsonContains('custom_properties->type', $type)
            ->first();

        return $media?->getUrl();
    }

    public function getMediaUrl(string $collection): ?string
    {
        return $this->getFirstMediaUrl($collection);
    }

    public function getGalleryUrls(): array
    {
        return $this->getMedia('gallery')
            ->map(fn($media) => $media->getUrl())
            ->toArray();
    }

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('logo')
            ->useDisk('public');

        $this->addMediaCollection('banner')
            ->useDisk('public');

        $this->addMediaCollection('banner2')
            ->useDisk('public');

        $this->addMediaCollection('gallery')
            ->useDisk('public');

        $this->addMediaCollection('policies')
            ->useDisk('public');
    }

    public function reviews(): HasManyThrough
    {
        return $this->hasManyThrough(
            Review::class,
            Product::class,
            'store_id',
            'product_id',
        );
    }

    // Cambia esto en app/Models/Store.php
    public function getAverageRatingAttribute($value): float
    {
        // Si $value ya existe (gracias a withAvg), lo usamos.
        // Si no existe (cuando consultas una sola tienda sin con conAvg), hace el fallback a la BD.
        $rating = $value ?? $this->reviews()->avg('rating') ?? 0;

        return round((float) $rating, 1);
    }

    public function getReviewCountAttribute(): int
    {
        return $this->reviews()->count();
    }

    public function storeReviews(): HasMany
    {
        return $this->hasMany(StoreReview::class);
    }

    public function getStoreAverageRatingAttribute(): float
    {
        return round($this->storeReviews()->avg('rating') ?? 0, 1);
    }

    public function getStoreReviewCountAttribute(): int
    {
        return $this->storeReviews()->count();
    }
    // app/Models/Store.php
    public function activeSubscription()
    {
        return $this->hasOne(Subscription::class)
            ->where('status', 'active')
            ->where('ends_at', '>=', now())
            ->latestOfMany();
    }
}
