<?php

declare(strict_types=1);

namespace App\Http\Requests\Security;

use App\Models\ProtectionRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class StoreProtectionRuleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'type' => ['required', 'string', Rule::in([
                ProtectionRule::TYPE_RATE_LIMIT,
                ProtectionRule::TYPE_IP_BLOCK,
                ProtectionRule::TYPE_GEO,
                ProtectionRule::TYPE_DEVICE,
                ProtectionRule::TYPE_CUSTOM,
            ])],
            'pattern' => ['nullable', 'string', 'max:255'],
            'severity' => ['required', 'string', Rule::in([
                ProtectionRule::SEVERITY_LOW,
                ProtectionRule::SEVERITY_MEDIUM,
                ProtectionRule::SEVERITY_HIGH,
            ])],
            'status' => ['required', 'string', Rule::in([
                ProtectionRule::STATUS_ACTIVE,
                ProtectionRule::STATUS_INACTIVE,
            ])],
            'priority' => ['nullable', 'integer', 'min:0', 'max:999'],
            'description' => ['nullable', 'string', 'max:1000'],
            'config' => ['nullable', 'array'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'El nombre de la regla es obligatorio.',
            'type.required' => 'El tipo de regla es obligatorio.',
            'type.in' => 'Tipo de regla no válido.',
            'severity.required' => 'La severidad es obligatoria.',
            'status.required' => 'El estado es obligatorio.',
        ];
    }
}
