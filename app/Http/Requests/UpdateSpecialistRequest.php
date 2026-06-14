<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * UpdateSpecialistRequest — Validación para actualizar un especialista.
 *
 * Todos los campos son opcionales (PATCH semántico).
 * El email se valida como único ignorando el propio registro.
 */
final class UpdateSpecialistRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // La autorización real se hace en el controller
    }

    public function rules(): array
    {
        // El ID del especialista viene en la ruta: /api/stores/me/specialists/{id}
        $specialistId = (int) $this->route('id');

        return [
            'nombres' => ['sometimes', 'string', 'max:255'],
            'apellidos' => ['sometimes', 'string', 'max:255'],
            'document_type' => ['sometimes', 'in:DNI,CE,PASAPORTE'],
            'document_number' => ['sometimes', 'string', 'max:20'],

            // Ignorar el email del propio especialista al validar unicidad
            'email' => [
                'sometimes',
                'email',
                'max:255',
                "unique:specialists,email,{$specialistId}",
            ],

            'category_id' => [
                'sometimes',
                'nullable',
                'integer',
                'exists:categories,id',
                function ($attribute, $value, $fail) {
                    if ($value === null) {
                        return;
                    }
                    $category = \App\Models\Category::find($value);
                    if (! $category) {
                        return;
                    }
                    if ($category->type !== 'service') {
                        $fail('La categoría debe ser de tipo servicio.');

                        return;
                    }
                    if ($category->parent_id === null) {
                        $fail('Debes seleccionar una categoría de nivel 2 (especialidad), no una categoría principal.');
                    }
                },
            ],

            'especialidad' => ['sometimes', 'string', 'max:255'],
            'sub_especialidad' => ['nullable', 'string', 'max:255'],
            'anios_experiencia' => ['nullable', 'integer', 'min:0', 'max:30'],
            'numero_colegiatura' => ['nullable', 'string', 'max:100'],
            'availability' => ['sometimes', 'in:Disponible,Indispuesto,Ocupado'],

            // ── Mensajes personalizados ────────────────────────────────────────
            'availability.in' => 'El estado debe ser Disponible, Indispuesto u Ocupado.',
            'category_id.exists' => 'La categoría seleccionada no existe.',
        ];
    }
}
