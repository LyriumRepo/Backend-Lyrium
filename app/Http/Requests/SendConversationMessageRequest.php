<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class SendConversationMessageRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'content' => ['required', 'string', 'max:5000'],
        ];
    }

    public function messages(): array
    {
        return [
            'content.required' => 'El mensaje no puede estar vacío.',
            'content.max' => 'El mensaje no puede exceder 5000 caracteres.',
        ];
    }
}
