<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class VerifyOtpRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'email' => 'required|string|email',
            'code' => 'required|string|size:6',
        ];
    }

    public function messages(): array
    {
        return [
            'code.size' => 'El código debe tener exactamente 6 dígitos.',
        ];
    }
}
