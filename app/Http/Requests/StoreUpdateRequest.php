<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class StoreUpdateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'trade_name' => ['sometimes', 'string', 'max:255'],
            'razon_social' => ['nullable', 'string', 'max:255'],
            'nombre_comercial' => ['nullable', 'string', 'max:255'],
            'corporate_email' => ['sometimes', 'email', 'max:255'],
            'description' => ['nullable', 'string'],
            'activity' => ['nullable', 'string', 'max:255'],
            'logo' => ['nullable', 'string', 'max:500'],
            'banner' => ['nullable', 'string', 'max:500'],
            'store_name' => ['nullable', 'string', 'max:255'],
            'category_id' => ['nullable', 'integer', 'exists:categories,id'],
            'address' => ['nullable', 'string'],
            'phone' => ['nullable', 'string', 'max:20'],
            'seller_type' => ['sometimes', 'string', 'in:products,services,both'],
            'rep_legal_nombre' => ['nullable', 'string', 'max:255'],
            'rep_legal_dni' => ['nullable', 'string', 'max:20'],
            'rep_legal_foto' => ['nullable', 'string', 'max:500'],
            'experience_years' => ['nullable', 'integer', 'min:0', 'max:100'],
            'tax_condition' => ['nullable', 'string', 'max:100'],
            'direccion_fiscal' => ['nullable', 'string'],
            'cuenta_bcp' => ['nullable', 'string', 'max:50'],
            'cci' => ['nullable', 'string', 'max:50'],
            'bank_secondary' => ['nullable', 'array'],
            'bank_secondary.bank' => ['nullable', 'string', 'max:255'],
            'bank_secondary.account' => ['nullable', 'string', 'max:50'],
            'bank_secondary.cci' => ['nullable', 'string', 'max:50'],
            'instagram' => ['nullable', 'string', 'max:255'],
            'facebook' => ['nullable', 'string', 'max:255'],
            'tiktok' => ['nullable', 'string', 'max:255'],
            'whatsapp' => ['nullable', 'string', 'max:50'],
            'youtube' => ['nullable', 'string', 'max:255'],
            'twitter' => ['nullable', 'string', 'max:255'],
            'linkedin' => ['nullable', 'string', 'max:255'],
            'website' => ['nullable', 'string', 'max:255'],
            'policies' => ['nullable', 'string'],
            'gallery' => ['nullable', 'array'],
            'gallery.*' => ['string', 'max:500'],
        ];
    }

    public function messages(): array
    {
        return [
            'category_id.exists' => 'La categoría seleccionada no existe.',
            'seller_type.in' => 'El tipo de vendedor debe ser: products, services o both.',
        ];
    }
}
