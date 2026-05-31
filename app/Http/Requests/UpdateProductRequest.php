<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Models\Product;
use Illuminate\Foundation\Http\FormRequest;

final class UpdateProductRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $product = Product::find($this->route('id'));
        $type = $this->input('type', $product?->type ?? 'physical');

        $rules = [
            // Campos base
            'name' => 'sometimes|string|min:3|max:200',
            'description' => 'nullable|string|max:5000',
            'short_description' => 'nullable|string|max:300',      // ← nuevo
            'price' => 'sometimes|numeric|min:0',
            'stock' => 'sometimes|integer|min:0',
            'category' => 'nullable|string',
            'image' => 'nullable|string',
            'sticker' => 'nullable|string|in:liquidacion,oferta,descuento,nuevo,bestseller,envio_gratis',
            'discountPercentage' => 'nullable|numeric|min:0|max:100',

            // Atributos principales (ficha de características)
            'mainAttributes' => 'nullable|array',
            'mainAttributes.*.values' => 'required_with:mainAttributes|array',
            'mainAttributes.*.values.label' => 'required_with:mainAttributes|string|max:100',
            'mainAttributes.*.values.value' => 'required_with:mainAttributes|string|max:255',

            // Atributos adicionales (uso, beneficios, etc.)
            'additionalAttributes' => 'nullable|array',
            'additionalAttributes.*.values' => 'required_with:additionalAttributes|array',
            'additionalAttributes.*.values.label' => 'required_with:additionalAttributes|string|max:100',
            'additionalAttributes.*.values.value' => 'required_with:additionalAttributes|string|max:255',

            // Ficha nutricional                             ← nuevo
            'servingNote' => 'nullable|string|max:200',
            'nutritionalAttributes' => 'nullable|array',
            'nutritionalAttributes.*.values' => 'required_with:nutritionalAttributes|array',
            'nutritionalAttributes.*.values.label' => 'required_with:nutritionalAttributes|string|max:100',
            'nutritionalAttributes.*.values.value' => 'required_with:nutritionalAttributes|string|max:100',
            'nutritionalAttributes.*.values.daily_value' => 'nullable|string|max:20',
        ];

        if ($type === 'physical') {
            $rules['weight'] = 'nullable|numeric|min:0';
            $rules['dimensions'] = 'nullable|string|max:100';
            $rules['expirationDate'] = 'nullable|date|after:today';
        }

        if ($type === 'digital') {
            $rules['downloadUrl'] = 'sometimes|url|max:500';
            $rules['downloadLimit'] = 'nullable|integer|min:1';
            $rules['fileType'] = 'nullable|string|max:20';
            $rules['fileSize'] = 'nullable|integer|min:0';
        }

        if ($type === 'service') {
            $rules['serviceDuration'] = 'sometimes|integer|min:1';
            $rules['serviceModality'] = 'sometimes|string|in:presencial,virtual,domicilio';
            $rules['serviceLocation'] = 'nullable|string|max:255';
        }

        return $rules;
    }

    public function messages(): array
    {
        return [
            'mainAttributes.*.values.label.required_with' => 'Cada característica debe tener un nombre.',
            'mainAttributes.*.values.value.required_with' => 'Cada característica debe tener un valor.',
            'additionalAttributes.*.values.label.required_with' => 'Cada atributo adicional debe tener un nombre.',
            'additionalAttributes.*.values.value.required_with' => 'Cada atributo adicional debe tener un valor.',
            'nutritionalAttributes.*.values.label.required_with' => 'Cada fila nutricional debe tener un nombre.',
            'nutritionalAttributes.*.values.value.required_with' => 'Cada fila nutricional debe tener un valor.',
            'sticker.in' => 'El sticker debe ser: liquidacion, oferta, descuento, nuevo, bestseller o envio_gratis.',
            'serviceModality.in' => 'La modalidad debe ser: presencial, virtual o domicilio.',
            'expirationDate.after' => 'La fecha de vencimiento debe ser posterior a hoy.',
            'short_description.max' => 'La descripción corta no puede superar los 300 caracteres.',
        ];
    }
}
