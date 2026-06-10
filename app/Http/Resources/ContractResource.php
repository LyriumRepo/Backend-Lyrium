<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

final class ContractResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->contract_number,
            'dbId' => $this->id,
            'storeId' => $this->store_id,
            'company' => $this->company,
            'ruc' => $this->ruc,
            'rep' => $this->representative,
            'dni' => $this->dni,
            'direccion' => $this->direccion,
            'admin_name' => $this->admin_name,
            'admin_phone' => $this->admin_phone,
            'admin_email' => $this->admin_email,
            'type' => $this->type,
            'modality' => $this->modality,
            'plan' => $this->plan,
            'status' => $this->status,
            'start' => $this->start_date?->toDateString(),
            'end' => $this->end_date?->toDateString(),
            'storage_path' => $this->file_path ?? 'Pendiente de Carga',
            'signed_file_path' => $this->signed_file_path,
            'has_signed_doc' => ! empty($this->signed_file_path),
            'notes' => $this->notes,
            'expiryUrgency' => $this->expiry_urgency,
            'auditTrail' => $this->whenLoaded('auditTrails', fn () => $this->auditTrails->map(fn ($trail) => [
                'timestamp' => $trail->created_at->toIso8601String(),
                'action' => $trail->action,
                'user' => $trail->user,
            ])->values()->all()),
            'store' => $this->whenLoaded('store', fn () => $this->store ? [
                'id' => $this->store->id,
                'tradeName' => $this->store->trade_name,
                'slug' => $this->store->slug,
            ] : null),
            'createdAt' => $this->created_at?->toIso8601String(),
        ];
    }
}
