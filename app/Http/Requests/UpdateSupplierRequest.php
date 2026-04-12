<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class UpdateSupplierRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $supplierId = $this->route('id');

        return [
            'name' => 'sometimes|string|min:2|max:255',
            'ruc' => 'nullable|string|size:11|unique:suppliers,ruc,'.$supplierId,
            'tipo' => 'nullable|string|in:Economista,Contador,Ingeniero',
            'especialidad' => 'nullable|string|max:255',
            'estado' => 'nullable|string|in:Activo,Suspendido,Finalizado',
            'fechaRenovacion' => 'nullable|date',
            'proyectos' => 'nullable|array',
            'proyectos.*' => 'string|max:255',
            'certificaciones' => 'nullable|array',
            'certificaciones.*' => 'string|max:255',
        ];
    }
}
