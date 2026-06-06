<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class StoreServiceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'benefits' => ['nullable', 'string'],
            'image' => ['nullable', 'string'],
            'price' => ['required', 'numeric', 'min:0'],
            'duration_minutes' => ['required', 'integer', 'min:15', 'max:480'],

            // ── NUEVOS CAMPOS DEL FRONTEND ───────────────────────────────────
            'buffer_minutes' => ['nullable', 'integer', 'min:0', 'max:120'],
            'is_home_service' => ['nullable', 'boolean'],
            'booking_advance_hours' => ['nullable', 'integer', 'min:0', 'max:168'],
            'max_capacity' => ['nullable', 'integer', 'min:1', 'max:100'],

            // Especialistas asignados (Paso 1 del Frontend)
            'specialist_ids' => ['nullable', 'array'],
            'specialist_ids.*' => ['required_with:specialist_ids', 'exists:specialists,id'],
            // ─────────────────────────────────────────────────────────────────

            'status' => ['sometimes', 'in:active,inactive,draft'], // Soportamos "draft" (borrador)
            'cancellation_policy' => ['sometimes', 'in:no_refund,flexible,strict'],
            'max_cancellations' => ['sometimes', 'integer', 'min:1', 'max:10'],

            'sticker' => ['nullable', 'string', 'in:nuevo,descuento,oferta,liquidacion,bestseller,envio_gratis'],
            'discount_percentage' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'settings' => ['nullable', 'array'],

            'category_id' => [
                'nullable',
                'exists:categories,id',
                function ($attribute, $value, $fail) {
                    if (! $value) {
                        return;
                    }

                    $category = \App\Models\Category::find($value);

                    // Debe ser de tipo servicio
                    if (! $category || $category->type !== 'service') {
                        $fail('La categoría debe ser de tipo servicio.');

                        return;
                    }

                    // Debe ser nivel 2: tener parent_id (no ser raíz)
                    if (is_null($category->parent_id)) {
                        $fail('Debes seleccionar una subcategoría, no una categoría principal.');

                        return;
                    }

                    // Su padre debe ser nivel 1: el padre no debe tener parent_id
                    $parent = \App\Models\Category::find($category->parent_id);
                    if (! $parent || ! is_null($parent->parent_id)) {
                        $fail('La subcategoría seleccionada no pertenece a una categoría principal válida.');

                        return;
                    }
                },
            ],
            'schedules' => ['sometimes', 'array'],
            'schedules.*.day_of_week' => ['required_with:schedules', 'in:monday,tuesday,wednesday,thursday,friday,saturday,sunday'],
            'schedules.*.start_time' => ['required_with:schedules', 'date_format:H:i'],
            'schedules.*.end_time' => ['required_with:schedules', 'date_format:H:i', 'after:schedules.*.start_time'],

            'schedules.*.specialist_id' => ['required_with:schedules', 'exists:specialists,id'],

            'schedules.*.max_appointments' => ['sometimes', 'integer', 'min:1', 'max:50'],
            'schedules.*.is_active' => ['sometimes', 'boolean'],
            'schedules.*.orden_bloque' => ['nullable', 'integer'],
        ];
    }

    public function messages(): array
    {
        return [
            'duration_minutes.min' => 'La duración mínima es de 15 minutos',
            'duration_minutes.max' => 'La duración máxima es de 8 horas',
            'schedules.*.end_time.after' => 'La hora de fin debe ser posterior a la hora de inicio',
        ];
    }
}
