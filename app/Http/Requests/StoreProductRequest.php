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
            'short_description' => 'nullable|string|max:300',
            'price' => 'required|numeric|min:0',
            'stock' => 'required|integer|min:0',
            'category' => 'nullable|string',
            'image' => 'nullable|string',
            'discountPercentage' => 'nullable|numeric|min:0|max:100',
            // En StoreProductRequest, reemplazar las reglas de atributos por estas:
            'mainAttributes'                        => 'nullable|array',
            'mainAttributes.*.values'               => 'required_with:mainAttributes|array',
            'mainAttributes.*.values.label'         => 'required_with:mainAttributes|string|max:100',
            'mainAttributes.*.values.value'         => 'required_with:mainAttributes|string|max:255',

            'additionalAttributes'                  => 'nullable|array',
            'additionalAttributes.*.values'         => 'required_with:additionalAttributes|array',
            'additionalAttributes.*.values.label'   => 'required_with:additionalAttributes|string|max:100',
            'additionalAttributes.*.values.value'   => 'required_with:additionalAttributes|string|max:255',

            'servingNote'                                    => 'nullable|string|max:200',
            'nutritionalAttributes'                          => 'nullable|array',
            'nutritionalAttributes.*.values'                 => 'required_with:nutritionalAttributes|array',
            'nutritionalAttributes.*.values.label'           => 'required_with:nutritionalAttributes|string|max:100',
            'nutritionalAttributes.*.values.value'           => 'required_with:nutritionalAttributes|string|max:100',
            'nutritionalAttributes.*.values.daily_value'     => 'nullable|string|max:20',

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
            'mainAttributes.*.values.label.required_with'        => 'Cada característica debe tener un nombre.',
            'mainAttributes.*.values.value.required_with'        => 'Cada característica debe tener un valor.',
            'additionalAttributes.*.values.label.required_with'  => 'Cada atributo adicional debe tener un nombre.',
            'additionalAttributes.*.values.value.required_with'  => 'Cada atributo adicional debe tener un valor.',
            'nutritionalAttributes.*.values.label.required_with' => 'Cada fila nutricional debe tener un nombre.',
            'nutritionalAttributes.*.values.value.required_with' => 'Cada fila nutricional debe tener un valor.',
            'type.in' => 'El tipo debe ser: physical, digital o service.',
            'downloadUrl.required' => 'La URL de descarga es obligatoria para productos digitales.',
            'serviceDuration.required' => 'La duración es obligatoria para servicios.',
            'serviceModality.required' => 'La modalidad es obligatoria para servicios.',
            'serviceModality.in' => 'La modalidad debe ser: presencial, virtual o domicilio.',
            'expirationDate.after' => 'La fecha de vencimiento debe ser posterior a hoy.',
        ];
    }
}
