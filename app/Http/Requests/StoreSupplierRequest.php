<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class StoreSupplierRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => 'required|string|min:2|max:255',
            'ruc' => 'nullable|string|size:11|unique:suppliers,ruc',
            'tipo' => 'nullable|string|in:Economista,Contador,Ingeniero',
            'especialidad' => 'nullable|string|max:255',
            'fechaRenovacion' => 'nullable|date',
            'proyectos' => 'nullable|array',
            'proyectos.*' => 'string|max:255',
            'certificaciones' => 'nullable|array',
            'certificaciones.*' => 'string|max:255',
        ];
    }

    public function messages(): array
    {
        return [
            'ruc.size' => 'El RUC debe tener exactamente 11 digitos.',
            'ruc.unique' => 'Este RUC ya esta registrado.',
            'tipo.in' => 'El tipo debe ser: Economista, Contador o Ingeniero.',
        ];
    }
}
