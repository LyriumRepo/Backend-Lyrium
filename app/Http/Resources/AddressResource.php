<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

final class AddressResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => (int) $this->id,
            'etiqueta' => $this->etiqueta,
            'destinatario' => $this->destinatario,
            'pais' => $this->pais,
            'departamento' => $this->departamento,
            'provincia' => $this->provincia,
            'distrito' => $this->distrito,
            'avenida' => $this->avenida,
            'numero' => $this->numero,
            'piso_lote' => $this->piso_lote,
            'referencia' => $this->referencia,
            'is_default' => (bool) $this->is_default,
            'created_at' => $this->created_at?->toDateTimeString(),
            'updated_at' => $this->updated_at?->toDateTimeString(),
        ];
    }
}
