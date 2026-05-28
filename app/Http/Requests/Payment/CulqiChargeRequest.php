<?php

declare(strict_types=1);

namespace App\Http\Requests\Payment;

/**
 * ARCHIVO: app/Http/Requests/Payment/CulqiChargeRequest.php
 */

use Illuminate\Foundation\Http\FormRequest;

final class CulqiChargeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // La auth la controla el middleware auth:sanctum en la ruta
    }

    public function rules(): array
    {
        return [
            'order_id'    => ['required', 'integer', 'exists:orders,id'],
            'culqi_token' => ['required', 'string', 'min:10'],   // tkn_test_xxx o tkn_live_xxx
            'email'       => ['required', 'email'],
        ];
    }

    public function messages(): array
    {
        return [
            'order_id.required'    => 'El ID de la orden es obligatorio.',
            'order_id.exists'      => 'La orden no existe.',
            'culqi_token.required' => 'El token de pago es obligatorio.',
            'culqi_token.min'      => 'El token de pago no es válido.',
            'email.required'       => 'El correo electrónico es obligatorio.',
            'email.email'          => 'El correo electrónico no es válido.',
        ];
    }
}
