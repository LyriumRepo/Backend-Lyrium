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
            'type' => 'sometimes|string|max:255',
            'modality' => 'sometimes|string|in:VIRTUAL,PHYSICAL',
            'start' => 'sometimes|date',
            'end' => 'nullable|date|after_or_equal:start',
            'notes' => 'nullable|string|max:2000',
        ];
    }
}
