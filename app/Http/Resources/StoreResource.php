<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

final class StoreResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => (string) $this->id,
            'userId' => $this->owner_id,
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
            'ruc' => $this->ruc,
            'razon_social' => $this->razon_social,
            'nombre_comercial' => $this->nombre_comercial,
            'rep_legal_nombre' => $this->rep_legal_nombre,
            'rep_legal_dni' => $this->rep_legal_dni,
            'rep_legal_foto' => $this->rep_legal_foto,
            'experience_years' => $this->experience_years,
            'tax_condition' => $this->tax_condition,
            'direccion_fiscal' => $this->direccion_fiscal,
            'cuenta_bcp' => $this->cuenta_bcp,
            'cci' => $this->cci,
            'bank_secondary' => $this->bank_secondary,
            'store_name' => $this->store_name,
            'category' => new CategoryResource($this->whenLoaded('category')),
            'category_id' => $this->category_id,
            'address' => $this->address,
            'instagram' => $this->instagram,
            'facebook' => $this->facebook,
            'tiktok' => $this->tiktok,
            'whatsapp' => $this->whatsapp,
            'youtube' => $this->youtube,
            'twitter' => $this->twitter,
            'linkedin' => $this->linkedin,
            'website' => $this->website,
            'social' => [
                'instagram' => $this->instagram,
                'facebook' => $this->facebook,
                'tiktok' => $this->tiktok,
                'whatsapp' => $this->whatsapp,
                'youtube' => $this->youtube,
                'twitter' => $this->twitter,
                'linkedin' => $this->linkedin,
                'website' => $this->website,
            ],
            'policies' => $this->policies,
            'policyFiles' => [
                'shipping' => $this->getPolicyUrl('shipping'),
                'return' => $this->getPolicyUrl('return'),
                'privacy' => $this->getPolicyUrl('privacy'),
            ],
            'status' => $this->status,
            'profile_complete' => $this->isProfileComplete(),
            'missing_profile_fields' => $this->missingProfileFields(),
            'sellerType' => $this->seller_type,
            'strikes' => $this->strikes,
            'commissionRate' => (float) $this->commission_rate,
            'totalSales' => (int) $this->total_sales,
            'totalOrders' => (int) $this->total_sales,
            'rating' => (float) $this->rating,
            'contractStatus' => $this->whenLoaded('contracts', function () {
                $contract = $this->contracts->first();
                return $contract ? $contract->status : null;
            }),
            'registeredAt' => $this->created_at?->toIso8601String(),
            'verifiedAt' => $this->approved_at?->toIso8601String(),
            'owner' => new UserResource($this->whenLoaded('owner')),
            'subscription' => $this->whenLoaded('subscription'),
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
