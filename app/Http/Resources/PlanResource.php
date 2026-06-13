<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

final class PlanResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $isAdmin = $request->user()?->hasRole('administrator') ?? $request->is('*/admin/*');

        $data = [
            'id' => $this->id,
            'name' => $this->name,
            'slug' => $this->slug,
            'monthly_fee' => $this->monthly_fee,
            'commission_rate' => $this->commission_rate,
            'has_membership_fee' => $this->has_membership_fee,
            'features' => $this->features,
            'detailed_benefits' => $this->detailed_benefits,
            'is_active' => $this->is_active,
            'badge' => $this->badge,
            'description' => $this->description,
            'requires_payment' => $this->requires_payment,
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];

        if ($isAdmin) {
            $data['timeline_icon'] = $this->timeline_icon;
            $data['css_color'] = $this->css_color;
            $data['accent_color'] = $this->accent_color;
            $data['enable_claim_lock'] = $this->enable_claim_lock;
            $data['claim_months'] = $this->claim_months;
            $data['subscribe_button_text'] = $this->subscribe_button_text;
            $data['currency'] = $this->currency;
            $data['period'] = $this->period;
            $data['price_annual'] = $this->price_annual;
            $data['price_text'] = $this->price_text;
            $data['price_subtext'] = $this->price_subtext;
            $data['use_price_mode'] = $this->use_price_mode;
            $data['compact_visible_count'] = $this->compact_visible_count;
            $data['trial_success_title'] = $this->trial_success_title;
            $data['trial_success_message'] = $this->trial_success_message;
            $data['trial_wait_message'] = $this->trial_wait_message;
            $data['claimed_button_text'] = $this->claimed_button_text;
            $data['claimed_warning_text'] = $this->claimed_warning_text;
            $data['subscriptions_count'] = $this->whenCounted('subscriptions');
            $data['active_subscriptions_count'] = $this->when(
                $this->relationLoaded('subscriptions'),
                fn () => $this->subscriptions->where('status', 'active')->where('ends_at', '>=', now())->count()
            );
        }

        return $data;
    }
}
