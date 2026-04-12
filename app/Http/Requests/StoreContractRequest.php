<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class StoreContractRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'storeId' => 'nullable|integer|exists:stores,id',
            'company' => 'required|string|max:255',
            'ruc' => 'nullable|string|size:11',
            'rep' => 'nullable|string|max:255',
            'type' => 'required|string|max:255',
            'modality' => 'required|string|in:VIRTUAL,PHYSICAL',
            'start' => 'required|date',
            'end' => 'nullable|date|after_or_equal:start',
            'notes' => 'nullable|string|max:2000',
        ];
    }

    public function messages(): array
    {
        return [
            'ruc.size' => 'El RUC debe tener exactamente 11 digitos.',
            'modality.in' => 'La modalidad debe ser VIRTUAL o PHYSICAL.',
            'end.after_or_equal' => 'La fecha fin debe ser igual o posterior a la fecha inicio.',
        ];
    }
}
