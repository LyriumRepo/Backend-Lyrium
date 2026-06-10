<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class UpdateAddressRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'etiqueta' => 'sometimes|string|in:casa,trabajo,otro',
            'destinatario' => 'sometimes|string|max:255',
            'pais' => 'sometimes|string|max:100',
            'departamento' => 'sometimes|string|max:100',
            'provincia' => 'sometimes|string|max:100',
            'distrito' => 'sometimes|string|max:100',
            'avenida' => 'sometimes|string|max:255',
            'numero' => 'sometimes|string|max:20',
            'piso_lote' => 'nullable|string|max:50',
            'referencia' => 'nullable|string|max:500',
            'is_default' => 'sometimes|boolean',
        ];
    }

    public function messages(): array
    {
        return [
            'etiqueta.in' => 'La etiqueta debe ser: casa, trabajo u otro.',
        ];
    }
}
