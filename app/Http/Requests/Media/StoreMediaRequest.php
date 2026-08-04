<?php

declare(strict_types=1);

namespace App\Http\Requests\Media;

use Illuminate\Foundation\Http\FormRequest;

final class StoreMediaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'file' => [
                'required',
                'file',
                'mimes:jpeg,jpg,png,webp,gif',
                'max:10240',
            ],
            // Solo usado por banners promocionales (ad_banners) de tienda; el resto
            // de endpoints que reutilizan esta request lo ignoran sin problema.
            'orientation' => ['sometimes', 'string', 'in:horizontal,vertical'],
        ];
    }

    public function messages(): array
    {
        return [
            'file.required' => 'El archivo es obligatorio.',
            'file.file' => 'El archivo debe ser un archivo válido.',
            'file.mimes' => 'El archivo debe ser una imagen (JPEG, PNG, WebP o GIF).',
            'file.max' => 'El archivo no debe superar los 10 MB.',
            'orientation.in' => 'La orientación debe ser horizontal o vertical.',
        ];
    }
}
