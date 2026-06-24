<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class StoreContractRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'storeId' => 'nullable|integer|exists:stores,id',
            'company' => 'required|string|max:255',
            'ruc' => 'nullable|string|size:11|regex:/^[0-9]+$/',
            'rep' => 'nullable|string|max:255',
            'dni' => 'nullable|string|size:8|regex:/^[0-9]+$/',
            'direccion' => 'nullable|string|max:500',
            'admin_name' => 'nullable|string|max:255',
            'admin_phone' => 'nullable|string|size:9|regex:/^[0-9]+$/',
            'admin_email' => 'nullable|email|max:255',
            'type' => 'nullable|string|max:255',
            'modality' => 'required|string|in:VIRTUAL,PHYSICAL',
            'plan' => 'nullable|string|max:100',
            'status' => 'nullable|string|in:ACTIVE,PENDING,EXPIRED',
            'start' => 'required|date',
            'end' => 'nullable|date|after_or_equal:start',
            'notes' => 'nullable|string|max:2000',
        ];
    }

    public function messages(): array
    {
        return [
            'ruc.size' => 'El RUC debe tener exactamente 11 dígitos.',
            'ruc.regex' => 'El RUC debe contener solo números.',
            'dni.size' => 'El DNI debe tener exactamente 8 dígitos.',
            'dni.regex' => 'El DNI debe contener solo números.',
            'admin_phone.size' => 'El teléfono debe tener exactamente 9 dígitos.',
            'admin_phone.regex' => 'El teléfono debe contener solo números.',
            'modality.in' => 'La modalidad debe ser VIRTUAL o PHYSICAL.',
            'end.after_or_equal' => 'La fecha fin debe ser igual o posterior a la fecha inicio.',
        ];
    }
}
