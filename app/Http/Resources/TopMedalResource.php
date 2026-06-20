<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

final class TopMedalResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $entity = $this->whenLoaded('medalable');

        return [
            'id' => (string) $this->id,
            'entity_type' => $this->entity_type,
            'entity' => $this->resolveEntity($entity),
            'rank_position' => $this->rank_position,
            'status' => $this->status,
            'visible' => (bool) $this->visible,
            'medal_image_url' => $this->medal_image_url,
            'times_entered' => (int) $this->times_entered,
            'times_exited' => (int) $this->times_exited,
            'detected_at' => $this->detected_at?->toISOString(),
            'approved_at' => $this->approved_at?->toISOString(),
            'suspended_at' => $this->suspended_at?->toISOString(),
            'grace_ends_at' => $this->grace_ends_at?->toISOString(),
            'created_at' => $this->created_at?->toISOString(),
        ];
    }

    private function resolveEntity($entity): ?array
    {
        if (! $entity) {
            return null;
        }

        return match ($this->entity_type) {
            'store' => [
                'id' => (string) $entity->id,
                'name' => $entity->store_name ?? $entity->trade_name,
                'slug' => $entity->slug,
                'logo' => $entity->logo,
            ],
            'product' => [
                'id' => (string) $entity->id,
                'name' => $entity->name,
                'slug' => $entity->slug,
                'image' => $entity->getMedia('images')->first()?->getUrl('thumb'),
            ],
            'service' => [
                'id' => (string) $entity->id,
                'name' => $entity->name,
                'slug' => $entity->slug,
                'image' => $entity->image,
            ],
            default => null,
        };
    }
}
