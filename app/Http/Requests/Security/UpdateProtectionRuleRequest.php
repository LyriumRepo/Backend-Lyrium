<?php

declare(strict_types=1);

namespace App\Http\Requests\Security;

use App\Models\ProtectionRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class UpdateProtectionRuleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['sometimes', 'string', 'max:255'],
            'type' => ['sometimes', 'string', Rule::in([
                ProtectionRule::TYPE_RATE_LIMIT,
                ProtectionRule::TYPE_IP_BLOCK,
                ProtectionRule::TYPE_GEO,
                ProtectionRule::TYPE_DEVICE,
                ProtectionRule::TYPE_CUSTOM,
            ])],
            'pattern' => ['nullable', 'string', 'max:255'],
            'severity' => ['sometimes', 'string', Rule::in([
                ProtectionRule::SEVERITY_LOW,
                ProtectionRule::SEVERITY_MEDIUM,
                ProtectionRule::SEVERITY_HIGH,
            ])],
            'status' => ['sometimes', 'string', Rule::in([
                ProtectionRule::STATUS_ACTIVE,
                ProtectionRule::STATUS_INACTIVE,
            ])],
            'priority' => ['nullable', 'integer', 'min:0', 'max:999'],
            'description' => ['nullable', 'string', 'max:1000'],
            'config' => ['nullable', 'array'],
        ];
    }
}
