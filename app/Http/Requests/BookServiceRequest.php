<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class BookServiceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'schedule_id' => ['required', 'integer', 'exists:service_schedules,id'],
            'appointment_date' => ['required', 'date', 'after:now'],
            'payment_method' => ['required', 'string', 'max:50'],
            'notes' => ['nullable', 'string', 'max:1000'],
            'customer_notes' => ['nullable', 'string', 'max:1000'],

            // ── NUEVOS CAMPOS SOPORTADOS ────────────────────────────────────
            'specialist_id' => ['nullable', 'integer', 'exists:specialists,id'],
            'start_time' => ['required', 'string', 'date_format:H:i'],
            'end_time' => ['nullable', 'string', 'date_format:H:i'],
        ];
    }

    public function messages(): array
    {
        return [
            'appointment_date.after' => 'La fecha de la cita debe ser en el futuro',
            'start_time.required' => 'La hora de inicio de la cita es obligatoria.',
            'specialist_id.exists' => 'El especialista seleccionado no es válido.',
        ];
    }
}
