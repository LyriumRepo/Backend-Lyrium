<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

final class VendedorListResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $activeSubscription = $this->subscriptions()
            ->where('status', 'active')
            ->where('ends_at', '>=', now())
            ->with('plan')
            ->latest()
            ->first();

        return [
            'id' => $this->id,
            'store_id' => $this->id,
            'trade_name' => $this->trade_name,
            'slug' => $this->slug,
            'status' => $this->status,
            'ruc' => $this->ruc,
            'commission_rate' => $this->commission_rate,
            'strikes' => $this->strikes,
            'seller' => $this->owner ? [
                'id' => $this->owner->id,
                'name' => $this->owner->name,
                'email' => $this->owner->email,
            ] : null,
            'subscription' => $activeSubscription ? [
                'id' => $activeSubscription->id,
                'plan_id' => $activeSubscription->plan_id,
                'plan_name' => $activeSubscription->plan->name,
                'plan_slug' => $activeSubscription->plan->slug,
                'plan_color' => $activeSubscription->plan->css_color,
                'monthly_fee' => $activeSubscription->plan->monthly_fee,
                'starts_at' => $activeSubscription->starts_at?->toIso8601String(),
                'ends_at' => $activeSubscription->ends_at?->toIso8601String(),
                'is_active' => $activeSubscription->isActive(),
            ] : null,
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
