<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class UpdateContractRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'storeId' => 'nullable|integer|exists:stores,id',
            'company' => 'sometimes|string|max:255',
            'ruc' => 'nullable|string|size:11',
            'rep' => 'nullable|string|max:255',
            'dni' => 'nullable|string|max:20',
            'direccion' => 'nullable|string|max:500',
            'admin_name' => 'nullable|string|max:255',
            'admin_phone' => 'nullable|string|max:20',
            'admin_email' => 'nullable|email|max:255',
            'type' => 'nullable|string|max:255',
            'modality' => 'sometimes|string|in:VIRTUAL,PHYSICAL',
            'plan' => 'nullable|string|max:100',
            'start' => 'sometimes|date',
            'end' => 'nullable|date|after_or_equal:start',
            'notes' => 'nullable|string|max:2000',
        ];
    }
}
