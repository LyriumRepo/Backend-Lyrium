<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class StoreAddressRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'etiqueta' => 'required|string|in:casa,trabajo,otro',
            'destinatario' => 'required|string|max:255',
            'pais' => 'required|string|max:100',
            'departamento' => 'required|string|max:100',
            'provincia' => 'required|string|max:100',
            'distrito' => 'required|string|max:100',
            'avenida' => 'required|string|max:255',
            'numero' => 'required|string|max:20',
            'piso_lote' => 'nullable|string|max:50',
            'referencia' => 'nullable|string|max:500',
            'is_default' => 'sometimes|boolean',
        ];
    }

    public function messages(): array
    {
        return [
            'etiqueta.required' => 'La etiqueta (casa, trabajo, otro) es obligatoria.',
            'etiqueta.in' => 'La etiqueta debe ser: casa, trabajo u otro.',
            'destinatario.required' => 'El nombre del destinatario es obligatorio.',
            'departamento.required' => 'El departamento es obligatorio.',
            'provincia.required' => 'La provincia es obligatoria.',
            'distrito.required' => 'El distrito es obligatorio.',
            'avenida.required' => 'La dirección es obligatoria.',
            'numero.required' => 'El número de domicilio es obligatorio.',
        ];
    }
}
