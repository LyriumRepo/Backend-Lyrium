<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\MediaLibrary\HasMedia;
use App\Traits\AuditableModel;
use Spatie\MediaLibrary\InteractsWithMedia;

final class Product extends Model implements HasMedia
{
    use AuditableModel, HasFactory, InteractsWithMedia, SoftDeletes;

    protected $fillable = [
        'store_id',
        'type',
        'name',
        'slug',
        'description',
        'short_description',
        'serving_note',
        'price',
        'regular_price',
        'stock',
        'weight',
        'dimensions',
        'image',
        'sticker',
        'discount_percentage',
        'status',
        'expiration_date',
        // Digital
        'download_url',
        'download_limit',
        'file_type',
        'file_size',
        // Service
        'service_duration',
        'service_modality',
        'service_location',
    ];

    protected function casts(): array
    {
        return [
            'price' => 'decimal:2',
            'regular_price' => 'decimal:2',
            'sale_price' => 'decimal:2',
            'stock' => 'integer',
            'weight' => 'decimal:2',
            'discount_percentage' => 'decimal:2',
            'expiration_date' => 'date',
            'download_limit' => 'integer',
            'file_size' => 'integer',
            'service_duration' => 'integer',
        ];
    }

    public function isPhysical(): bool
    {
        return $this->type === 'physical';
    }

    public function isDigital(): bool
    {
        return $this->type === 'digital';
    }

    public function isService(): bool
    {
        return $this->type === 'service';
    }

    public function store(): BelongsTo
    {
        return $this->belongsTo(Store::class);
    }

    public function categories(): BelongsToMany
    {
        return $this->belongsToMany(Category::class, 'category_product');
    }

    public function attributes(): HasMany
    {
        return $this->hasMany(ProductAttribute::class);
    }

    public function mainAttributes(): HasMany
    {
        return $this->hasMany(ProductAttribute::class)->where('type', 'main');
    }

    public function additionalAttributes(): HasMany
    {
        return $this->hasMany(ProductAttribute::class)->where('type', 'additional');
    }

    public function decrementStock(int $quantity): void
    {
        $stockBefore = $this->stock;
        $this->decrement('stock', $quantity);
        $this->refresh();
        $stockAfter = $this->stock;

        $seller = $this->store?->user;
        if (! $seller) {
            return;
        }

        // Notificar solo cuando el stock cruza un umbral hacia abajo por primera vez.
        // 'out'     : pasa de > 0  a  ≤ 0
        // 'critical': pasa de > 5  a  1-5  (sin haber llegado a 'out')
        if ($stockAfter <= 0 && $stockBefore > 0) {
            $seller->notify(new \App\Notifications\StockAlertNotification($this, 'out'));
        } elseif ($stockAfter <= 5 && $stockBefore > 5) {
            $seller->notify(new \App\Notifications\StockAlertNotification($this, 'critical'));
        }
    }

    public function reviews(): HasMany
    {
        return $this->hasMany(Review::class);
    }

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('images')
            ->acceptsMimeTypes(['image/jpeg', 'image/png', 'image/webp', 'image/gif'])
            ->useDisk('public')
            ->withResponsiveImages();
    }

    public function registerMediaConversions(?\Spatie\MediaLibrary\MediaCollections\Models\Media $media = null): void
    {
        $this->addMediaConversion('thumb')
            ->width(150)
            ->height(150)
            ->sharpen(10)
            ->nonQueued();

        $this->addMediaConversion('medium')
            ->width(300)
            ->height(300)
            ->sharpen(10)
            ->nonQueued();

        $this->addMediaConversion('large')
            ->width(800)
            ->height(800)
            ->sharpen(10)
            ->nonQueued();
    }

    public function getAverageRatingAttribute(): float
    {
        // Añadido (float) para evitar el TypeError en round()
        return round((float) ($this->reviews()->avg('rating') ?? 0), 1);
    }

    public function getReviewCountAttribute(): int
    {
        return $this->reviews()->count();
    }

    public function nutritionalAttributes(): HasMany
    {
        return $this->hasMany(ProductAttribute::class)->where('type', 'nutritional');
    }
}
