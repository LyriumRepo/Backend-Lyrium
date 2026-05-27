<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

final class ProductRankingResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'             => (string) $this->id,
            'name'           => $this->name,
            'slug'           => $this->slug,
            'rating_average' => (float) $this->rating_promedio,
            'rating_count'   => (int) $this->rating_count,
            'price'          => (float) $this->price,
            'in_stock'       => $this->stock > 0,
            'image'          => $this->resource->getMedia('images')->first()?->getUrl('thumb'),
            'categories'     => $this->whenLoaded(
                'categories',
                fn() =>
                $this->categories->map(fn($c) => ['name' => $c->name, 'slug' => $c->slug])->all()
            ),
            'store' => $this->whenLoaded('store', fn() => [
                'id'   => (string) $this->store->id,
                'name' => $this->store->store_name,
                'slug' => $this->store->slug,
                'logo' => $this->store->logo,
            ]),
        ];
    }
}
