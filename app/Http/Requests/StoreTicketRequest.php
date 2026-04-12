<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class StoreTicketRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'asunto'      => ['required', 'string', 'min:5', 'max:200'],
            'mensaje'     => ['required_without:adjuntos', 'nullable', 'string', 'min:10', 'max:5000'],
            'tipo_ticket' => ['required', 'in:tech,admin,info,comment,followup,payments,documentation'],
            'criticidad'  => ['required', 'in:baja,media,alta,critica'],
            'adjuntos'    => ['nullable', 'array', 'max:3'],
            'adjuntos.*'  => ['image', 'mimes:jpeg,png,jpg,webp', 'max:5120'],
        ];
    }

    public function messages(): array
    {
        return [
            'asunto.required'              => 'El asunto del ticket es obligatorio.',
            'asunto.min'                   => 'El asunto debe tener al menos 5 caracteres.',
            'mensaje.required_without'     => 'Escribe un mensaje o adjunta al menos una imagen.',
            'mensaje.min'                  => 'El mensaje debe tener al menos 10 caracteres.',
            'tipo_ticket.required'         => 'El tipo de ticket es obligatorio.',
            'tipo_ticket.in'               => 'Tipo de ticket inválido.',
            'criticidad.required'          => 'La criticidad es obligatoria.',
            'criticidad.in'                => 'Criticidad inválida.',
            'adjuntos.max'                 => 'Puedes adjuntar un máximo de 3 imágenes.',
            'adjuntos.*.image'             => 'Solo se permiten archivos de imagen.',
            'adjuntos.*.mimes'             => 'Las imágenes deben ser jpeg, png, jpg o webp.',
            'adjuntos.*.max'               => 'Cada imagen no puede superar los 5 MB.',
        ];
    }
}
