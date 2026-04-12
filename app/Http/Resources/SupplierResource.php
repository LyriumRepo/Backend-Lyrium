<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

final class SupplierResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'nombre' => $this->name,
            'slug' => $this->slug,
            'ruc' => $this->ruc,
            'tipo' => $this->type,
            'especialidad' => $this->especialidad,
            'estado' => $this->status,
            'fechaRenovacion' => $this->fecha_renovacion?->toDateString(),
            'proyectos' => $this->proyectos,
            'certificaciones' => $this->certificaciones,
            'totalRecibos' => $this->total_recibos,
            'totalGastado' => (float) $this->total_gastado,
            'createdAt' => $this->created_at?->toIso8601String(),
        ];
    }
}
