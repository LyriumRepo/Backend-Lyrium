<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class CreateBookingSessionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'service_id' => ['required', 'integer', 'exists:services,id'],
            'email' => ['required', 'email'],
            'schedule_id' => ['required', 'integer', 'exists:service_schedules,id'],
            'specialist_id' => ['nullable', 'integer', 'exists:specialists,id'],
            'appointment_date' => ['required', 'date', 'after:now'],
            'start_time' => ['required', 'string', 'date_format:H:i'],
            'customer_notes' => ['nullable', 'string', 'max:1000'],
        ];
    }
}
