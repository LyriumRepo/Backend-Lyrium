<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class ConfigureStoreShippingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'method_id' => ['required', 'integer', 'exists:shipping_methods,id'],
            'is_enabled' => ['sometimes', 'boolean'],
            'additional_cost' => ['sometimes', 'numeric', 'min:0'],
            'handling_time_days' => ['sometimes', 'integer', 'min:0', 'max:30'],
        ];
    }
}
