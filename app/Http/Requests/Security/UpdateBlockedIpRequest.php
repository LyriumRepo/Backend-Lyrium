<?php

declare(strict_types=1);

namespace App\Http\Requests\Security;

use App\Models\BlockedIp;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class UpdateBlockedIpRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'reason' => ['sometimes', 'string', 'max:500'],
            'status' => ['sometimes', 'string', Rule::in([
                BlockedIp::STATUS_BLOCKED,
                BlockedIp::STATUS_UNBLOCKED,
                BlockedIp::STATUS_FLAGGED,
                BlockedIp::STATUS_WHITELISTED,
            ])],
            'expires_at' => ['nullable', 'date', 'after:now'],
        ];
    }

    public function messages(): array
    {
        return [
            'status.in' => 'Estado no válido. Use: blocked, unblocked, flagged o whitelisted.',
            'expires_at.after' => 'La fecha de expiración debe ser futura.',
        ];
    }
}
