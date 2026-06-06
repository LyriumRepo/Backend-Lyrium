<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class ScanDocumentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasRole(['administrator', 'logistics_operator']) ?? false;
    }

    public function rules(): array
    {
        return [
            'file' => ['required_without:file_path', 'file', 'mimes:pdf', 'max:10240'],
            'file_path' => ['required_without:file', 'string', 'max:500'],
        ];
    }

    public function messages(): array
    {
        return [
            'file.required_without' => 'Debes enviar un archivo PDF o una ruta de archivo.',
            'file.mimes' => 'Solo se aceptan archivos PDF.',
            'file.max' => 'El archivo no debe superar los 10 MB.',
        ];
    }
}
