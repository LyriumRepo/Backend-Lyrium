<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class ScanBatchStoreRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasRole(['administrator', 'logistics_operator']) ?? false;
    }

    public function rules(): array
    {
        return [
            'file_path' => ['required', 'string', 'max:500'],
            'supplier_id' => ['required', 'integer', 'exists:suppliers,id'],
            'lines' => ['required', 'array', 'min:1'],
            'lines.*.date' => ['required', 'string', 'max:20'],
            'lines.*.description' => ['required', 'string', 'max:255'],
            'lines.*.reference' => ['nullable', 'string', 'max:100'],
            'lines.*.amount' => ['required', 'numeric', 'min:0.01', 'max:9999999.99'],
            'lines.*.glossary_key' => ['nullable', 'string', 'max:50'],
            'lines.*.glossary_description' => ['nullable', 'string', 'max:255'],
            'lines.*.hour' => ['nullable', 'string', 'max:10'],
            'lines.*.med' => ['nullable', 'string', 'max:10'],
            'lines.*.tipo' => ['nullable', 'string', 'max:10'],
            'lines.*.place' => ['nullable', 'string', 'max:255'],
            'lines.*.balance' => ['nullable', 'numeric'],
            'period' => ['nullable', 'string', 'max:50'],
            'period_full' => ['nullable', 'string', 'max:100'],
            'opening_balance' => ['nullable', 'numeric'],
            'closing_balance' => ['nullable', 'numeric'],
        ];
    }

    public function messages(): array
    {
        return [
            'lines.*.date.required' => 'Cada línea debe tener una fecha.',
            'lines.*.description.required' => 'Cada línea debe tener una descripción.',
            'lines.*.amount.required' => 'Cada línea debe tener un monto.',
        ];
    }
}
