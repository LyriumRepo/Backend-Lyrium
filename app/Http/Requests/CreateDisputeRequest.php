<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Models\Dispute;
use Illuminate\Validation\Rule;

final class CreateDisputeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'order_id' => ['required', 'integer', 'exists:orders,id'],
            'user_id' => ['required', 'integer', 'exists:users,id'],
            'store_id' => ['required', 'integer', 'exists:stores,id'],
            'type' => ['required', 'string', Rule::in(Dispute::TYPES)],
            'priority' => ['sometimes', 'string', Rule::in(Dispute::PRIORITIES)],
            'description' => ['required', 'string', 'min:20', 'max:5000'],
            'message' => ['sometimes', 'string', 'max:5000'],
        ];
    }

    public function messages(): array
    {
        return [
            'description.min' => 'La descripción debe tener al menos 20 caracteres',
            'description.max' => 'La descripción no puede exceder 5000 caracteres',
        ];
    }
}
