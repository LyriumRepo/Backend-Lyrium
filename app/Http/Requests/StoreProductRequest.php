<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class StoreProductRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $rules = [
            'type' => 'required|string|in:physical,digital,service',
            'name' => 'required|string|min:3|max:200',
            'description' => 'nullable|string|max:5000',
            'price' => 'required|numeric|min:0',
            'stock' => 'required|integer|min:0',
            'category' => 'nullable|string',
            'image' => 'nullable|string',
            'discountPercentage' => 'nullable|numeric|min:0|max:100',
            'mainAttributes' => 'nullable|array',
            'mainAttributes.*.values' => 'array',
            'mainAttributes.*.values.*' => 'string',
            'additionalAttributes' => 'nullable|array',
            'additionalAttributes.*.values' => 'array',
            'additionalAttributes.*.values.*' => 'string',
        ];

        $type = $this->input('type', 'physical');

        if ($type === 'physical') {
            $rules['weight'] = 'nullable|numeric|min:0';
            $rules['dimensions'] = 'nullable|string|max:100';
            $rules['expirationDate'] = 'nullable|date|after:today';
        }

        if ($type === 'digital') {
            $rules['downloadUrl'] = 'required|url|max:500';
            $rules['downloadLimit'] = 'nullable|integer|min:1';
            $rules['fileType'] = 'nullable|string|max:20';
            $rules['fileSize'] = 'nullable|integer|min:0';
            $rules['stock'] = 'nullable|integer|min:0'; // stock opcional para digitales
        }

        if ($type === 'service') {
            $rules['serviceDuration'] = 'required|integer|min:1';
            $rules['serviceModality'] = 'required|string|in:presencial,virtual,domicilio';
            $rules['serviceLocation'] = 'nullable|string|max:255';
            $rules['stock'] = 'nullable|integer|min:0'; // stock opcional para servicios
        }

        return $rules;
    }

    public function messages(): array
    {
        return [
            'type.in' => 'El tipo debe ser: physical, digital o service.',
            'downloadUrl.required' => 'La URL de descarga es obligatoria para productos digitales.',
            'serviceDuration.required' => 'La duración es obligatoria para servicios.',
            'serviceModality.required' => 'La modalidad es obligatoria para servicios.',
            'serviceModality.in' => 'La modalidad debe ser: presencial, virtual o domicilio.',
            'expirationDate.after' => 'La fecha de vencimiento debe ser posterior a hoy.',
        ];
    }
}
