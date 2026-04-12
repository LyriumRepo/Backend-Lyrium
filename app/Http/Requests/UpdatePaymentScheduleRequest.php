<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Models\PaymentSchedule;
use Illuminate\Validation\Rule;

final class UpdatePaymentScheduleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['sometimes', 'string', 'max:100'],
            'day' => ['sometimes', 'string', Rule::in(PaymentSchedule::DAYS)],
            'cutoff_time' => ['sometimes', 'date_format:H:i:s'],
            'is_active' => ['sometimes', 'boolean'],
        ];
    }
}
