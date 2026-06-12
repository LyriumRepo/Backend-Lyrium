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
            'sticker' => 'nullable|string|in:liquidacion,oferta,descuento,nuevo,bestseller,envio_gratis,organic,natural,eco,premium,vegan',
            'discountPercentage' => 'nullable|numeric|min:0|max:100',
            // Atributos — el frontend envía { values: [label, value] }
            'mainAttributes' => 'nullable|array',
            'mainAttributes.*.values' => 'present|array',
            'mainAttributes.*.values.0' => 'string|max:100',
            'mainAttributes.*.values.1' => 'string|max:255',

            'additionalAttributes' => 'nullable|array',
            'additionalAttributes.*.values' => 'present|array',
            'additionalAttributes.*.values.0' => 'string|max:100',
            'additionalAttributes.*.values.1' => 'string|max:255',

            'servingNote' => 'nullable|string|max:200',
            'nutritionalAttributes' => 'nullable|array',
            'nutritionalAttributes.*.values' => 'present|array',
            'nutritionalAttributes.*.values.0' => 'string|max:100',
            'nutritionalAttributes.*.values.1' => 'string|max:100',
            'nutritionalAttributes.*.values.2' => 'nullable|string|max:20',
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
            'sticker.in' => 'El sticker debe ser: liquidacion, oferta, descuento, nuevo, bestseller o envio_gratis.',
            'type.in' => 'El tipo debe ser: physical, digital o service.',
            'sticker.in' => 'El sticker seleccionado no es válido. Opciones: liquidacion, oferta, descuento, nuevo, bestseller, envio_gratis.',
            'downloadUrl.required' => 'La URL de descarga es obligatoria para productos digitales.',
            'serviceDuration.required' => 'La duración es obligatoria para servicios.',
            'serviceModality.required' => 'La modalidad es obligatoria para servicios.',
            'serviceModality.in' => 'La modalidad debe ser: presencial, virtual o domicilio.',
            'expirationDate.after' => 'La fecha de vencimiento debe ser posterior a hoy.',
            'mainAttributes.*.values.0.max' => 'El nombre de cada característica no debe exceder 100 caracteres.',
            'mainAttributes.*.values.1.max' => 'El valor de cada característica no debe exceder 255 caracteres.',
            'additionalAttributes.*.values.0.max' => 'El nombre de cada atributo adicional no debe exceder 100 caracteres.',
            'additionalAttributes.*.values.1.max' => 'El valor de cada atributo adicional no debe exceder 255 caracteres.',
        ];
    }
}
