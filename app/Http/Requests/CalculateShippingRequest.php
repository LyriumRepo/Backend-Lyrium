<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class CalculateShippingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'store_id' => ['required', 'integer', 'exists:stores,id'],
            'weight' => ['required', 'numeric', 'min:0.01'],
            'order_total' => ['required', 'numeric', 'min:0'],
            'department' => ['required', 'string', 'max:100'],
            'zone_id' => ['nullable', 'integer', 'exists:shipping_zones,id'],
        ];
    }
}
