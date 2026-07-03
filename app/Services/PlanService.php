<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Plan;
use App\Models\Subscription;
use App\Models\SystemConfig;
use Illuminate\Support\Str;

final class PlanService
{
    public function listAll(): array
    {
        $plans = Plan::orderBy('monthly_fee')->get();

        return [
            'data' => $plans,
        ];
    }

    public function listActive(): array
    {
        $plans = Plan::active()->orderBy('monthly_fee')->get();

        return [
            'data' => $plans,
        ];
    }

    public function findById(int $id): Plan
    {
        return Plan::findOrFail($id);
    }

    public function create(array $data): Plan
    {
        if (! isset($data['slug']) || empty($data['slug'])) {
            $data['slug'] = Str::slug($data['name']);
        }

        $data['features'] = $data['features'] ?? [];
        $data['detailed_benefits'] = $data['detailed_benefits'] ?? [];

        return Plan::create($data);
    }

    public function update(int $id, array $data): Plan
    {
        $plan = Plan::findOrFail($id);

        if (isset($data['features'])) {
            $data['features'] = array_values($data['features']);
        }

        if (isset($data['detailed_benefits'])) {
            $data['detailed_benefits'] = array_values($data['detailed_benefits']);
        }

        $plan->update($data);

        return $plan->fresh();
    }

    public function delete(int $id): void
    {
        $plan = Plan::findOrFail($id);

        $activeSubscriptions = Subscription::query()
            ->where('plan_id', $id)
            ->where('status', 'active')
            ->where('ends_at', '>=', now())
            ->count();

        if ($activeSubscriptions > 0) {
            throw new \RuntimeException(
                "No se puede eliminar el plan porque tiene {$activeSubscriptions} suscripción(es) activa(s)."
            );
        }

        $plan->delete();
    }

    public function toggleActive(int $id): Plan
    {
        $plan = Plan::findOrFail($id);
        $plan->update(['is_active' => ! $plan->is_active]);

        return $plan->fresh();
    }

    public function updateIcon(int $id, string $icon): Plan
    {
        $plan = Plan::findOrFail($id);
        $plan->update(['timeline_icon' => $icon]);

        return $plan->fresh();
    }

    public function getButtonColors(): array
    {
        return [
            'subscribeBg' => SystemConfig::getByKey('plan_btn_subscribe_bg', '#3b82f6'),
            'subscribeColor' => SystemConfig::getByKey('plan_btn_subscribe_color', '#ffffff'),
            'currentBg' => SystemConfig::getByKey('plan_btn_current_bg', '#e5e7eb'),
            'currentColor' => SystemConfig::getByKey('plan_btn_current_color', '#9ca3af'),
            'lockedBg' => SystemConfig::getByKey('plan_btn_locked_bg', '#9ca3af'),
            'lockedColor' => SystemConfig::getByKey('plan_btn_locked_color', '#e5e7eb'),
            'warningColor' => SystemConfig::getByKey('plan_btn_warning_color', '#ef4444'),
        ];
    }

    public function saveButtonColors(array $colors): array
    {
        $mapping = [
            'subscribeBg' => 'plan_btn_subscribe_bg',
            'subscribeColor' => 'plan_btn_subscribe_color',
            'currentBg' => 'plan_btn_current_bg',
            'currentColor' => 'plan_btn_current_color',
            'lockedBg' => 'plan_btn_locked_bg',
            'lockedColor' => 'plan_btn_locked_color',
            'warningColor' => 'plan_btn_warning_color',
        ];

        foreach ($colors as $key => $value) {
            if (isset($mapping[$key])) {
                SystemConfig::setByKey($mapping[$key], $value);
            }
        }

        return $this->getButtonColors();
    }

    public function resetButtonColors(): array
    {
        $defaults = [
            'plan_btn_subscribe_bg' => '#3b82f6',
            'plan_btn_subscribe_color' => '#ffffff',
            'plan_btn_current_bg' => '#e5e7eb',
            'plan_btn_current_color' => '#9ca3af',
            'plan_btn_locked_bg' => '#9ca3af',
            'plan_btn_locked_color' => '#e5e7eb',
            'plan_btn_warning_color' => '#ef4444',
        ];

        foreach ($defaults as $key => $value) {
            SystemConfig::setByKey($key, $value);
        }

        return $this->getButtonColors();
    }

    protected static array $capabilitiesCache = [];

    public function storeCapabilities(\App\Models\Store $store): array
    {
        $storeId = $store->id;

        if (! isset(static::$capabilitiesCache[$storeId])) {
            $plan = $store->activeSubscription?->plan;

            static::$capabilitiesCache[$storeId] = $plan?->capabilities
                ?? Plan::where('slug', 'emprende')->first()?->capabilities
                ?? [];
        }

        return static::$capabilitiesCache[$storeId];
    }

    public function can(\App\Models\Store $store, string $capability): bool
    {
        return (bool) data_get($this->storeCapabilities($store), $capability, false);
    }

    public function limit(\App\Models\Store $store, string $capability): int
    {
        return (int) data_get($this->storeCapabilities($store), $capability, 0);
    }

    public function isUnlimited(\App\Models\Store $store, string $capability): bool
    {
        return $this->limit($store, $capability) === -1;
    }

    public function exceedsLimit(\App\Models\Store $store, string $capability, int $currentCount): bool
    {
        $limit = $this->limit($store, $capability);

        return $limit !== -1 && $currentCount >= $limit;
    }

    public function getDefaultPlansData(): array
    {
        $plans = Plan::orderBy('monthly_fee')->get();

        $result = [];
        foreach ($plans as $plan) {
            $result[$plan->slug] = [
                'id' => $plan->slug,
                'name' => $plan->name,
                'slug' => $plan->slug,
                'price' => (float) $plan->monthly_fee,
                'priceAnnual' => $plan->price_annual ?? ((float) $plan->monthly_fee * 12),
                'currency' => $plan->currency ?? 'S/',
                'period' => $plan->period ?? '/mes',
                'periodAnnual' => $plan->period_annual ?? '/año',
                'usePriceMode' => $plan->use_price_mode ?? true,
                'priceText' => $plan->price_text ?? ($plan->monthly_fee == 0 ? 'Gratis' : "S/ {$plan->monthly_fee}"),
                'priceSubtext' => $plan->price_subtext ?? '/mes',
                'description' => $plan->description ?? '',
                'badge' => $plan->badge ?? '',
                'cssColor' => $plan->css_color ?? '#3b82f6',
                'accentColor' => $plan->accent_color ?? '#2563eb',
                'requiresPayment' => $plan->requires_payment ?? $plan->monthly_fee > 0,
                'features' => $plan->features ?? [],
                'detailedBenefits' => $plan->detailed_benefits ?? [],
                'isActive' => $plan->is_active ?? true,
                'timelineIcon' => $plan->timeline_icon ?? 'star',
                'commissionRate' => (float) $plan->commission_rate,
                'enableClaimLock' => $plan->enable_claim_lock ?? false,
                'claimMonths' => $plan->claim_months ?? 1,
                'subscribeButtonText' => $plan->subscribe_button_text ?? 'Suscribirse',
                'compactVisibleCount' => $plan->compact_visible_count ?? 5,
            ];
        }

        return $result;
    }
}
