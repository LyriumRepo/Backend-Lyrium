<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class StoreExpenseRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->hasRole(['administrator', 'logistics_operator']);
    }

    public function rules(): array
    {
        return [
            'supplier_id' => ['required', 'integer', 'exists:suppliers,id'],
            'concept' => ['required', 'string', 'max:255'],
            'amount' => ['required', 'numeric', 'min:0.01', 'max:9999999.99'],
            'status' => ['sometimes', 'in:Pagado,Pendiente,Anulado'],
            'issued_at' => ['required', 'date', 'before_or_equal:today'],
            'paid_at' => ['nullable', 'date', 'after_or_equal:issued_at'],
            'voucher_type' => ['nullable', 'string', 'max:50'],
            'voucher_number' => ['nullable', 'string', 'max:50'],
            'file_url' => ['nullable', 'string', 'max:500'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ];
    }
}
