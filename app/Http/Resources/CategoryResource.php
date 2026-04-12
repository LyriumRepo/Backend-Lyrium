<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

final class CategoryResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'slug' => $this->slug,
            'image' => $this->image ? ['src' => asset($this->image)] : null,
            'description' => $this->description ?? '',
            'type' => $this->type ?? 'product',
            'sort_order' => $this->sort_order ?? 0,
            'count' => $this->products_count ?? 0,
            'parent' => $this->parent_id ?? 0,
            'children' => CategoryResource::collection($this->whenLoaded('children')),
        ];
    }
}
