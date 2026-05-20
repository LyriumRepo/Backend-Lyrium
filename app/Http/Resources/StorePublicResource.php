<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

final class StorePublicResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => (string) $this->id,
            'storeName' => $this->store_name,
            'slug' => $this->slug,
            'logo' => $this->getMediaUrl('logo'),
            'banner' => $this->getMediaUrl('banner'),
            'banner2' => $this->getMediaUrl('banner2'),
            'gallery' => $this->getGalleryUrls(),
            'description' => $this->description,
            'activity' => $this->activity,
            'email' => $this->corporate_email,
            'phone' => $this->phone,
            'address' => $this->address,
            'category' => new CategoryResource($this->whenLoaded('category')),
            'category_id' => $this->category_id,
            'instagram' => $this->instagram,
            'facebook' => $this->facebook,
            'tiktok' => $this->tiktok,
            'whatsapp' => $this->whatsapp,
            'youtube' => $this->youtube,
            'website' => $this->website,
            'social' => [
                'instagram' => $this->instagram,
                'facebook' => $this->facebook,
                'tiktok' => $this->tiktok,
                'whatsapp' => $this->whatsapp,
                'youtube' => $this->youtube,
                'website' => $this->website,
            ],
            'status' => $this->status,
            'totalSales' => (int) $this->total_sales,
            'rating' => (float) $this->rating,
            'registeredAt' => $this->created_at?->toIso8601String(),
            'subscription' => $this->whenLoaded('subscription', fn () => [
                'plan' => $this->subscription?->plan ? [
                    'id' => $this->subscription->plan->id,
                    'name' => $this->subscription->plan->name,
                    'slug' => $this->subscription->plan->slug,
                ] : null,
            ]),
            'layout' => $this->layout,
            'branches' => $this->whenLoaded('branches', fn () => $this->branches->map(fn ($branch) => [
                'id' => $branch->id,
                'name' => $branch->name,
                'address' => $branch->address,
                'city' => $branch->city,
                'phone' => $branch->phone,
                'hours' => $branch->hours,
                'is_principal' => $branch->is_principal,
                'maps_url' => $branch->maps_url,
                'is_active' => $branch->is_active,
            ])),
        ];
    }
}
