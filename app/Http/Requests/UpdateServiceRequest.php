<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class UpdateServiceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['sometimes', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'price' => ['sometimes', 'numeric', 'min:0'],
            'duration_minutes' => ['sometimes', 'integer', 'min:15', 'max:480'],
            'status' => ['sometimes', 'in:active,inactive'],
            'cancellation_policy' => ['sometimes', 'in:no_refund,flexible,strict'],
            'max_cancellations' => ['sometimes', 'integer', 'min:1', 'max:10'],
            'category_id' => ['nullable', 'exists:categories,id', function ($attribute, $value, $fail) {
                if ($value) {
                    $category = \App\Models\Category::find($value);
                    if ($category && $category->type !== 'service') {
                        $fail('La categoría debe ser de tipo servicio.');
                    }
                }
            }],
            'schedules' => ['sometimes', 'array'],
            'schedules.*.day_of_week' => ['required_with:schedules', 'in:monday,tuesday,wednesday,thursday,friday,saturday,sunday'],
            'schedules.*.start_time' => ['required_with:schedules', 'date_format:H:i'],
            'schedules.*.end_time' => ['required_with:schedules', 'date_format:H:i', 'after:schedules.*.start_time'],
            'schedules.*.max_appointments' => ['sometimes', 'integer', 'min:1', 'max:50'],
            'schedules.*.is_active' => ['sometimes', 'boolean'],
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
