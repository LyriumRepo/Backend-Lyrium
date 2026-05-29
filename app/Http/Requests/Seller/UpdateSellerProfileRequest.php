<?php

declare(strict_types=1);

namespace App\Http\Requests\Seller;

use Illuminate\Foundation\Http\FormRequest;

final class UpdateSellerProfileRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'display_name' => ['sometimes', 'string', 'min:2', 'max:100'],
            'name' => ['sometimes', 'string', 'min:2', 'max:100'],
            'username' => ['sometimes', 'string', 'min:3', 'max:30', 'unique:users,username,'.auth()->id()],
<<<<<<< HEAD
            'email' => ['sometimes', 'email', 'max:255'],
=======
            'email' => ['sometimes', 'email', 'unique:users,email,'.auth()->id()],
>>>>>>> rama-calderon
            'phone' => ['nullable', 'string', 'max:20'],
            'phone_2' => ['nullable', 'string', 'max:20'],
            'secondary_email' => ['nullable', 'email', 'max:255'],
            'landline' => ['nullable', 'string', 'max:20'],
            'birthday' => ['nullable', 'date'],
            'avatar' => ['nullable', 'string'],
            'document_type' => ['nullable', 'string', 'max:10'],
            'document_number' => ['nullable', 'string', 'max:20'],
        ];
    }

    public function messages(): array
    {
        return [
            'username.unique' => 'Este nombre de usuario ya está en uso.',
            'username.min' => 'El nombre de usuario debe tener al menos 3 caracteres.',
        ];
    }
}
