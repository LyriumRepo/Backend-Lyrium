<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\TopMedalResource;
use App\Models\Store;
use App\Models\TopMedal;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class SellerMedalController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();

        $storeIds = $user->ownedStores()->pluck('id');

        if ($storeIds->isEmpty()) {
            return response()->json([
                'success' => true,
                'data' => [],
            ]);
        }

        $productIds = $storeIds->isEmpty() ? [] : \App\Models\Product::whereIn('store_id', $storeIds)->pluck('id');
        $serviceIds = $storeIds->isEmpty() ? [] : \App\Models\Service::whereIn('store_id', $storeIds)->pluck('id');

        $medals = TopMedal::with('medalable')
            ->where(function ($q) use ($storeIds) {
                $q->where('entity_type', 'store')->whereIn('medalable_id', $storeIds);
            })
            ->orWhere(function ($q) use ($productIds) {
                $q->where('entity_type', 'product')->whereIn('medalable_id', $productIds);
            })
            ->orWhere(function ($q) use ($serviceIds) {
                $q->where('entity_type', 'service')->whereIn('medalable_id', $serviceIds);
            })
            ->orderByDesc('created_at')
            ->get();

        return response()->json([
            'success' => true,
            'data' => TopMedalResource::collection($medals),
        ]);
    }

    public function toggleVisibility(Request $request, TopMedal $medal): JsonResponse
    {
        $user = $request->user();

        if (! $this->medalBelongsToUser($medal, $user)) {
            return response()->json([
                'success' => false,
                'message' => 'This medal does not belong to your store.',
            ], 403);
        }

        if (! $medal->isApproved()) {
            return response()->json([
                'success' => false,
                'message' => 'Only approved medals can toggle visibility.',
            ], 422);
        }

        $medal->update(['visible' => ! $medal->visible]);

        return response()->json([
            'success' => true,
            'message' => $medal->visible ? 'Medal is now visible to the public.' : 'Medal is now hidden.',
            'data' => new TopMedalResource($medal),
        ]);
    }

    private function medalBelongsToUser(TopMedal $medal, $user): bool
    {
        $storeIds = $user->ownedStores()->pluck('id');

        if ($medal->entity_type === 'store') {
            return $storeIds->contains((int) $medal->medalable_id);
        }

        if ($medal->entity_type === 'product') {
            return \App\Models\Product::whereIn('store_id', $storeIds)
                ->where('id', (int) $medal->medalable_id)
                ->exists();
        }

        if ($medal->entity_type === 'service') {
            return \App\Models\Service::whereIn('store_id', $storeIds)
                ->where('id', (int) $medal->medalable_id)
                ->exists();
        }

        return false;
    }
}
