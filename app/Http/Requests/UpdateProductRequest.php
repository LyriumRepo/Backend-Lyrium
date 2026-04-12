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
            'name' => 'sometimes|string|min:3|max:200',
            'description' => 'nullable|string|max:5000',
            'price' => 'sometimes|numeric|min:0',
            'stock' => 'sometimes|integer|min:0',
            'category' => 'nullable|string',
            'image' => 'nullable|string',
            'sticker' => 'nullable|string|in:liquidacion,oferta,descuento,nuevo,bestseller,envio_gratis',
            'discountPercentage' => 'nullable|numeric|min:0|max:100',
            'mainAttributes' => 'nullable|array',
            'additionalAttributes' => 'nullable|array',
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
}
