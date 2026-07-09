<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

// app/Http/Resources/AdminPlanResource.php
final class AdminPlanResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
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
            'timeline_icon' => $this->timeline_icon,
            'css_color' => $this->css_color,
            'accent_color' => $this->accent_color,
            'enable_claim_lock' => $this->enable_claim_lock,
            'claim_months' => $this->claim_months,
            'subscribe_button_text' => $this->subscribe_button_text,
            'currency' => $this->currency,
            'period' => $this->period,
            'price_annual' => $this->price_annual,
            'price_text' => $this->price_text,
            'price_subtext' => $this->price_subtext,
            'use_price_mode' => $this->use_price_mode,
            'compact_visible_count' => $this->compact_visible_count,
            'bg_image' => $this->bg_image,
            'bg_image_fit' => $this->bg_image_fit,
            'bg_image_position' => $this->bg_image_position,
            'show_bg_in_card' => $this->show_bg_in_card,
            'trial_success_title' => $this->trial_success_title,
            'trial_success_message' => $this->trial_success_message,
            'trial_wait_message' => $this->trial_wait_message,
            'claimed_button_text' => $this->claimed_button_text,
            'claimed_warning_text' => $this->claimed_warning_text,
            'subscriptions_count' => $this->whenCounted('subscriptions'),
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
