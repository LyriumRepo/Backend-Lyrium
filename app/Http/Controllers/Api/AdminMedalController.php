<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\TopMedalResource;
use App\Models\TopMedal;
use App\Models\SystemConfig;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class AdminMedalController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = TopMedal::with('medalable');

        if ($request->filled('entity_type')) {
            $query->byEntityType($request->entity_type);
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $medals = $query->orderByDesc('created_at')->paginate($request->per_page ?? 20);

        return response()->json([
            'success' => true,
            'data' => TopMedalResource::collection($medals),
            'meta' => [
                'current_page' => $medals->currentPage(),
                'per_page' => $medals->perPage(),
                'total' => $medals->total(),
                'total_pages' => $medals->lastPage(),
            ],
        ]);
    }

    public function approve(Request $request, TopMedal $medal): JsonResponse
    {
        if ($medal->isApproved()) {
            return response()->json([
                'success' => false,
                'message' => 'Medal is already approved.',
            ], 422);
        }

        $medal->update([
            'status' => 'approved',
            'visible' => true,
            'approved_at' => now(),
            'approved_by' => $request->user()->id,
        ]);

        $medal->load('medalable');

        return response()->json([
            'success' => true,
            'message' => 'Medal approved successfully.',
            'data' => new TopMedalResource($medal),
        ]);
    }

    public function suspend(Request $request, TopMedal $medal): JsonResponse
    {
        if ($medal->isSuspended()) {
            return response()->json([
                'success' => false,
                'message' => 'Medal is already suspended.',
            ], 422);
        }

        $medal->update([
            'status' => 'suspended',
            'visible' => false,
            'suspended_at' => now(),
            'grace_ends_at' => null,
            'times_exited' => $medal->times_exited + 1,
        ]);

        $medal->load('medalable');

        return response()->json([
            'success' => true,
            'message' => 'Medal suspended successfully.',
            'data' => new TopMedalResource($medal),
        ]);
    }

}
