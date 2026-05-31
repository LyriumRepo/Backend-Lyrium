<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

final class ExpenseResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'receipt_number' => $this->receipt_number,
            'concept' => $this->concept,
            'amount' => (float) $this->amount,
            'status' => $this->status,
            'issued_at' => $this->issued_at?->toDateString(),
            'paid_at' => $this->paid_at?->toDateString(),
            'voucher_type' => $this->voucher_type,
            'voucher_number' => $this->voucher_number,
            'file_url' => $this->file_url,
            'notes' => $this->notes,
            'created_at' => $this->created_at->toISOString(),

            // Supplier snapshot
            'supplier' => $this->whenLoaded('supplier', fn () => [
                'id' => $this->supplier->id,
                'name' => $this->supplier->name,
                'especialidad' => $this->supplier->especialidad,
                'type' => $this->supplier->type,
            ]),

            // Quién registró
            'registered_by' => $this->whenLoaded('registeredBy', fn () => [
                'id' => $this->registeredBy->id,
                'name' => $this->registeredBy->name,
                'email' => $this->registeredBy->email,
            ]),
        ];
    }
}
