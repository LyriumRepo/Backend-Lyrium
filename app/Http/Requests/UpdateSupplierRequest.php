<?php

declare(strict_types=1);

namespace App\Http\Requests; // Ajusta el namespace si es diferente

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule; // 👈 Asegúrate de importar esto

final class UpdateSupplierRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        // 1. Extraemos el ID dinámicamente, asegurando que lo encuentre
        // Si tu ruta es /api/suppliers/{supplier}, leerá 'supplier'. Si es {id}, leerá 'id'.
        $supplierId = $this->route('supplier') ?? $this->route('id');

        return [
            'name' => 'sometimes|string|min:2|max:255',

            // 2. Usamos Rule::unique para que ignore de manera segura el ID actual
            'ruc' => [
                'nullable',
                'string',
                'size:11',
                Rule::unique('suppliers', 'ruc')->ignore($supplierId)
            ],

            'tipo' => 'nullable|string|in:Economista,Contador,Ingeniero',

            // 3. CORRECCIÓN: Actualizamos los estados para que coincidan EXACTAMENTE con el Frontend
            'estado' => 'nullable|string|in:Activo,Suspendido,Inactivo,En Pausa',

            'especialidad' => 'nullable|string|max:255',
            'fechaRenovacion' => 'nullable|date',
            'proyectos' => 'nullable|array',
            'proyectos.*' => 'string|max:255',
            'certificaciones' => 'nullable|array',
            'certificaciones.*' => 'string|max:255',
        ];
    }
}
