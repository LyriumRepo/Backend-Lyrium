<?php

declare(strict_types=1);

namespace App\Http\Requests\Plan;

use Illuminate\Foundation\Http\FormRequest;

final class StorePlanRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'slug' => ['required', 'string', 'max:255', 'unique:plans,slug'],
            'monthly_fee' => ['required', 'numeric', 'min:0'],
            'commission_rate' => ['required', 'numeric', 'min:0', 'max:1'],
            'has_membership_fee' => ['boolean'],
            'features' => ['nullable', 'array'],
            'features.*.text' => ['required_with:features', 'string', 'max:255'],
            'features.*.active' => ['boolean'],
            'detailed_benefits' => ['nullable', 'array'],
            'detailed_benefits.*.emoji' => ['nullable', 'string', 'max:10'],
            'detailed_benefits.*.title' => ['required_with:detailed_benefits', 'string', 'max:255'],
            'detailed_benefits.*.description' => ['nullable', 'string', 'max:500'],
            'detailed_benefits.*.color' => ['nullable', 'string', 'max:20'],
            'is_active' => ['boolean'],
            'timeline_icon' => ['nullable', 'string', 'max:50'],
            'badge' => ['nullable', 'string', 'max:100'],
            'description' => ['nullable', 'string', 'max:1000'],
            'css_color' => ['nullable', 'string', 'max:20'],
            'accent_color' => ['nullable', 'string', 'max:20'],
            'requires_payment' => ['boolean'],
            'enable_claim_lock' => ['boolean'],
            'claim_months' => ['nullable', 'integer', 'min:1', 'max:120'],
            'subscribe_button_text' => ['nullable', 'string', 'max:100'],
            'currency' => ['nullable', 'string', 'max:10'],
            'period' => ['nullable', 'string', 'max:20'],
            'price_annual' => ['nullable', 'numeric', 'min:0'],
            'price_text' => ['nullable', 'string', 'max:50'],
            'price_subtext' => ['nullable', 'string', 'max:50'],
            'use_price_mode' => ['boolean'],
            'compact_visible_count' => ['nullable', 'integer', 'min:1', 'max:50'],
            'bg_image' => ['nullable', 'string'],
            'bg_image_fit' => ['nullable', 'string', 'max:20'],
            'bg_image_position' => ['nullable', 'string', 'max:20'],
            'show_bg_in_card' => ['boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'El nombre del plan es obligatorio.',
            'slug.required' => 'El slug del plan es obligatorio.',
            'slug.unique' => 'Ya existe un plan con este slug.',
            'monthly_fee.required' => 'La tarifa mensual es obligatoria.',
            'commission_rate.required' => 'La tasa de comisión es obligatoria.',
            'features.*.text.required_with' => 'Cada característica debe tener un texto.',
            'detailed_benefits.*.title.required_with' => 'Cada beneficio debe tener un título.',
        ];
    }
}
