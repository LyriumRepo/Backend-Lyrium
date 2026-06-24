<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * StoreSpecialistRequest — Validación para crear un especialista.
 *
 * El vendedor autenticado solo puede crear especialistas para su propia tienda.
 * El email debe ser único en la tabla specialists (se usa para Google Calendar).
 */
final class StoreSpecialistRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // La autorización real se hace en el controller
    }

    public function rules(): array
    {
        return [
            // ── Datos de identidad ────────────────────────────────────────
            'nombres' => ['required', 'string', 'max:255'],
            'apellidos' => ['required', 'string', 'max:255'],
            'document_type' => ['required', 'in:DNI,CE,PASAPORTE'],
            'document_number' => ['required', 'string', 'max:20'],

            // Email único — solo para Google Calendar, nunca mostrado al cliente
            'email' => ['required', 'email', 'max:255', 'unique:specialists,email'],

            'category_id' => [
                'nullable',
                'integer',
                'exists:categories,id',
                function ($attribute, $value, $fail) {
                    if ($value === null) {
                        return; // nullable: permitido sin categoría
                    }
                    $category = \App\Models\Category::find($value);
                    if (! $category) {
                        return; // exists rule ya lo captura
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

            // ── Datos profesionales ───────────────────────────────────────
            'especialidad' => ['required', 'string', 'max:255'],
            'sub_especialidad' => ['nullable', 'string', 'max:255'],
            'anios_experiencia' => ['nullable', 'integer', 'min:0', 'max:30'],
            'numero_colegiatura' => ['nullable', 'string', 'max:100'],

            // ── Estado y foto ─────────────────────────────────────────────
            'availability' => ['sometimes', 'in:Disponible,Indispuesto,Ocupado'],

            'availability.in' => 'El estado debe ser Disponible, Indispuesto u Ocupado.',
            'category_id.exists' => 'La categoría seleccionada no existe.',
        ];
    }

    /**
     * Prepara los datos antes de la validación.
     * Si no se provee google_calendar_id, usa el email como ID de calendario.
     */
    protected function prepareForValidation(): void
    {
        if (empty($this->google_calendar_id) && ! empty($this->email)) {
            $this->merge([
                'google_calendar_id' => $this->email,
            ]);
        }
    }
}
