<?php

declare(strict_types=1);

namespace App\Http\Requests\Security;

use App\Models\BlockedIp;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class StoreBlockedIpRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'ip_address' => [
                'required',
                'string',
                'ip',
                Rule::unique('blocked_ips', 'ip_address'),
            ],
            'reason' => ['required', 'string', 'max:500'],
            'status' => ['required', 'string', Rule::in([
                BlockedIp::STATUS_BLOCKED,
                BlockedIp::STATUS_FLAGGED,
                BlockedIp::STATUS_WHITELISTED,
            ])],
            'expires_at' => ['nullable', 'date', 'after:now'],
        ];
    }

    public function messages(): array
    {
        return [
            'ip_address.required' => 'La dirección IP es obligatoria.',
            'ip_address.ip' => 'Debe ser una dirección IP válida.',
            'ip_address.unique' => 'Esta IP ya está registrada.',
            'reason.required' => 'El motivo es obligatorio.',
            'status.required' => 'El estado es obligatorio.',
            'status.in' => 'Estado no válido. Use: blocked, flagged o whitelisted.',
            'expires_at.after' => 'La fecha de expiración debe ser futura.',
        ];
    }
}
