<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class StoreOperationalRoleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->hasRole('administrator');
    }

    public function rules(): array
    {
        return [
            'name'         => ['required', 'string', 'max:100'],
            'code'         => ['required', 'string', 'max:50', 'alpha_dash', Rule::unique('operational_roles', 'code')],
            'description'  => ['nullable', 'string', 'max:500'],
            'is_active'    => ['sometimes', 'boolean'],
            'modules'      => ['nullable', 'array'],
            'modules.*'    => ['string', 'max:80'],
            'requires_2fa' => ['sometimes', 'boolean'],
        ];
    }
}
