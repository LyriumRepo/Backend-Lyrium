<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

final class Plan extends Model
{
    protected $fillable = [
        'name',
        'slug',
        'monthly_fee',
        'is_lifetime',
        'lifetime_price',
        'commission_rate',
        'has_membership_fee',
        'features',
        'detailed_benefits',
        'capabilities',
        'is_active',
        'timeline_icon',
        'badge',
        'description',
        'css_color',
        'accent_color',
        'requires_payment',
        'enable_claim_lock',
        'claim_months',
        'subscribe_button_text',
        'currency',
        'period',
        'price_annual',
        'price_text',
        'price_subtext',
        'use_price_mode',
        'compact_visible_count',
        'bg_image',
        'bg_image_fit',
        'bg_image_position',
        'show_bg_in_card',
        'trial_success_title',
        'trial_success_message',
        'trial_wait_message',
        'claimed_button_text',
        'claimed_warning_text',
    ];

    protected function casts(): array
    {
        return [
            'monthly_fee' => 'decimal:2',
            'is_lifetime' => 'boolean',
            'lifetime_price' => 'decimal:2',
            'price_annual' => 'decimal:2',
            'commission_rate' => 'decimal:4',
            'has_membership_fee' => 'boolean',
            'is_active' => 'boolean',
            'requires_payment' => 'boolean',
            'enable_claim_lock' => 'boolean',
            'use_price_mode' => 'boolean',
            'show_bg_in_card' => 'boolean',
            'features' => 'array',
            'detailed_benefits' => 'array',
            'capabilities' => 'array',
        ];
    }

    public function subscriptions(): HasMany
    {
        return $this->hasMany(Subscription::class);
    }

    public function planRequests(): HasMany
    {
        return $this->hasMany(PlanRequest::class);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * % de descuento aplicado sobre (precio_mensual x meses) según la duración elegida.
     * Tabla de "Precios de Suscripción de mis Planes.md": 10/20/30/40% para 1/2/3/4 años.
     * Verificado contra los 4 totales del MD para el plan Crece (S/40 base): 432/768/1008/1152.
     */
    public static function discountPercentForMonths(int $months): float
    {
        return match ($months) {
            12 => 10.0,
            24 => 20.0,
            36 => 30.0,
            48 => 40.0,
            default => 0.0,
        };
    }

    public function capability(string $key): mixed
    {
        return data_get($this->capabilities, $key);
    }
}
