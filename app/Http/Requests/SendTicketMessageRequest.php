<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class SendTicketMessageRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'content'        => ['required_without:attachments', 'nullable', 'string', 'min:1', 'max:5000'],
            'attachments'    => ['required_without:content', 'nullable', 'array', 'max:3'],
            'attachments.*'  => ['image', 'mimes:jpeg,png,jpg,webp', 'max:5120'],
            'type'           => ['sometimes', 'in:normal,quick_reply,escalation,system'],
        ];
    }

    public function messages(): array
    {
        return [
            'content.required_without'       => 'Debes escribir un mensaje o adjuntar al menos una imagen.',
            'attachments.required_without'   => 'Debes escribir un mensaje o adjuntar al menos una imagen.',
            'attachments.max'                => 'Puedes adjuntar un máximo de 3 imágenes por mensaje.',
            'attachments.*.image'            => 'Solo se permiten archivos de imagen.',
            'attachments.*.mimes'            => 'Las imágenes deben ser jpeg, png, jpg o webp.',
            'attachments.*.max'              => 'Cada imagen no puede superar los 5 MB.',
        ];
    }
}
