<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\TrainingResource;
use App\Models\Store;
use App\Models\Training;
use App\Models\TrainingProgress;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class SellerTrainingController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $store = Store::where('owner_id', $request->user()->id)->firstOrFail();

        $trainings = Training::where('is_published', true)
            ->orderBy('sort_order')
            ->orderByDesc('created_at')
            ->get();

        $completedIds = TrainingProgress::where('store_id', $store->id)
            ->whereNotNull('completed_at')
            ->pluck('training_id')
            ->toArray();

        $data = TrainingResource::collection($trainings)->map(function ($resource) use ($completedIds) {
            $training = $resource->resource;
            $training->completed = in_array($training->id, $completedIds);
            return $resource;
        });

        return response()->json([
            'success' => true,
            'data' => $data,
        ]);
    }

    public function markCompleted(Request $request, int $id): JsonResponse
    {
        $store = Store::where('owner_id', $request->user()->id)->firstOrFail();
        $training = Training::findOrFail($id);

        TrainingProgress::updateOrCreate(
            ['store_id' => $store->id, 'training_id' => $training->id],
            ['completed_at' => now()],
        );

        return response()->json(['success' => true, 'message' => 'Capacitación marcada como completada']);
    }

    public function markIncomplete(Request $request, int $id): JsonResponse
    {
        $store = Store::where('owner_id', $request->user()->id)->firstOrFail();

        TrainingProgress::where('store_id', $store->id)
            ->where('training_id', $id)
            ->delete();

        return response()->json(['success' => true, 'message' => 'Capacitación marcada como pendiente']);
    }

    public function stats(Request $request): JsonResponse
    {
        $store = Store::where('owner_id', $request->user()->id)->firstOrFail();

        $total = Training::where('is_published', true)->count();
        $completed = TrainingProgress::where('store_id', $store->id)
            ->whereNotNull('completed_at')
            ->count();
        $required = Training::where('is_published', true)->where('is_required', true)->count();
        $requiredCompleted = TrainingProgress::where('store_id', $store->id)
            ->whereNotNull('completed_at')
            ->whereIn('training_id', Training::where('is_required', true)->pluck('id'))
            ->count();

        return response()->json([
            'success' => true,
            'data' => [
                'total' => $total,
                'completed' => $completed,
                'required' => $required,
                'required_completed' => $requiredCompleted,
            ],
        ]);
    }
}
