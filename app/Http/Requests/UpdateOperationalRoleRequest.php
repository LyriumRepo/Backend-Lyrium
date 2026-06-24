<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class UpdateOperationalRoleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->hasRole('administrator');
    }

    public function rules(): array
    {
        $id = $this->route('operationalRole');

        return [
            'name' => ['sometimes', 'string', 'max:100'],
            'code' => ['sometimes', 'string', 'max:50', 'alpha_dash', Rule::unique('operational_roles', 'code')->ignore($id)],
            'description' => ['nullable', 'string', 'max:500'],
            'is_active' => ['sometimes', 'boolean'],
            'modules' => ['nullable', 'array'],
            'modules.*' => ['string', 'max:80'],
            'requires_2fa' => ['sometimes', 'boolean'],
        ];
    }
}
