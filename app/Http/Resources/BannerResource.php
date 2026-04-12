<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

final class BannerResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $imagenes = [$this->imagen];
        if ($this->imagen_mobile) {
            $imagenes[] = $this->imagen_mobile;
        }

        return [
            'id' => $this->id,
            'titulo' => $this->titulo,
            'descripcion' => $this->descripcion,
            'enlace' => $this->enlace,
            'imagenes' => $imagenes,
        ];
    }
}
