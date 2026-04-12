<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Seller\UpdateSellerProfileRequest;
use App\Http\Resources\SellerResource;
use App\Http\Resources\StoreResource;
use App\Models\Store;
use Illuminate\Http\JsonResponse;

final class SellerController extends Controller
{
    /**
     * GET /api/seller/profile
     * Get current seller's profile.
     */
    public function profile(): JsonResponse
    {
        $user = auth()->user();

        return $this->success(new SellerResource($user));
    }

    /**
     * PUT /api/seller/profile
     * Update current seller's profile.
     */
    public function updateProfile(UpdateSellerProfileRequest $request): JsonResponse
    {
        $user = auth()->user();
        $validated = $request->validated();

        $user->update($validated);

        return $this->success(new SellerResource($user->fresh()));
    }

    /**
     * GET /api/seller/store
     * Get current seller's store data.
     */
    public function store(): JsonResponse
    {
        $user = auth()->user();

        $store = Store::where('owner_id', $user->id)->first();

        if (! $store) {
            return $this->notFound('No tienes una tienda registrada.');
        }

        $store->load(['subscription']);

        return $this->success(new StoreResource($store));
    }
}
