<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Models\Dispute;
use Illuminate\Validation\Rule;

final class ResolveDisputeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'resolution' => ['required', 'string', Rule::in(Dispute::RESOLUTIONS)],
            'resolution_notes' => ['nullable', 'string', 'max:2000'],
            'refund_amount' => ['nullable', 'numeric', 'min:0'],
        ];
    }
}
