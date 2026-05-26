<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class StoreConversationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'store_id' => ['required', 'integer', 'exists:stores,id'],
            'category' => ['required', 'string', 'in:informacion,positivo,negativo,logistica,facturacion'],
            'subject' => ['required', 'string', 'max:500'],
            'message' => ['required', 'string', 'max:5000'],
        ];
    }

    public function messages(): array
    {
        return [
            'store_id.required' => 'Debes seleccionar un vendedor.',
            'store_id.exists' => 'El vendedor seleccionado no existe.',
            'category.required' => 'La categoría es requerida.',
            'category.in' => 'Categoría inválida.',
            'subject.required' => 'El asunto es requerido.',
            'subject.max' => 'El asunto no puede exceder 500 caracteres.',
            'message.required' => 'El mensaje es requerido.',
            'message.max' => 'El mensaje no puede exceder 5000 caracteres.',
        ];
    }
}
