<?php

declare(strict_types=1);

namespace App\Http\Requests\Order;

use Illuminate\Foundation\Http\FormRequest;

final class CreateOrderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'payment_method' => ['nullable', 'string', 'max:50'],
            'shipping_name' => ['nullable', 'string', 'max:200'],
            'shipping_email' => ['nullable', 'email', 'max:200'],
            'shipping_phone' => ['nullable', 'string', 'max:50'],
            'shipping_address' => ['nullable', 'string', 'max:500'],
            'shipping_city' => ['nullable', 'string', 'max:100'],
            'shipping_postal_code' => ['nullable', 'string', 'max:20'],
            'shipping_notes' => ['nullable', 'string', 'max:500'],
            'shipping_type' => ['nullable', 'string', 'max:50'],
            'carrier' => ['nullable', 'string', 'max:50'],
            'branch_id' => ['nullable', 'integer', 'exists:store_branches,id'],
            'shipping_cost' => ['nullable', 'numeric', 'min:0'],
            'notes' => ['nullable', 'string', 'max:1000'],
            'coupon_code' => ['nullable', 'string', 'max:50'],
            'lirios_used' => ['nullable', 'integer', 'min:0'],
            'store_shipping' => ['nullable', 'array'],
            'store_shipping.*.store_id' => ['required', 'integer', 'exists:stores,id'],
            'store_shipping.*.shipping_cost' => ['required', 'numeric', 'min:0'],
            // IDs de los ítems que el cliente dejó marcados en el checkout — si se envían,
            // solo esos productos/reservas de servicio pasan a la orden; el resto permanece
            // en el carrito. Si se omiten, se incluye todo (compatibilidad hacia atrás).
            'selected_product_ids' => ['sometimes', 'array'],
            'selected_product_ids.*' => ['integer'],
            'selected_service_hold_ids' => ['sometimes', 'array'],
            'selected_service_hold_ids.*' => ['integer'],
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            if ($this->has('subtotal')) {
                $subtotal = (float) $this->input('subtotal');
                if ($subtotal < 20) {
                    $validator->errors()->add('subtotal', 'El monto mínimo de la orden es S/ 20.00.');
                }
            }
        });
    }
}
