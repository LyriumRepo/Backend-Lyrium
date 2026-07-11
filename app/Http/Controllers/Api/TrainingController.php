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
use Illuminate\Support\Facades\Storage;

final class TrainingController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $trainings = Training::orderBy('sort_order')
            ->orderByDesc('created_at')
            ->paginate(min((int) $request->query('per_page', 50), 100));

        return response()->json([
            'success' => true,
            'data' => TrainingResource::collection($trainings->items()),
            'meta' => [
                'current_page' => $trainings->currentPage(),
                'per_page' => $trainings->perPage(),
                'total' => $trainings->total(),
                'total_pages' => $trainings->lastPage(),
            ],
        ]);
    }

    public function show(int $id): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => new TrainingResource(Training::findOrFail($id)),
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:5000'],
            'url' => ['required', 'url', 'max:500'],
            'platform' => ['nullable', 'string', 'max:30'],
            'thumbnail' => ['nullable', 'string', 'max:500'],
            'category' => ['nullable', 'string', 'max:255'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
            'is_required' => ['nullable', 'boolean'],
            'is_published' => ['nullable', 'boolean'],
        ]);

        $training = Training::create($data);

        return response()->json(['success' => true, 'data' => new TrainingResource($training)], 201);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $training = Training::findOrFail($id);

        $data = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:5000'],
            'url' => ['required', 'url', 'max:500'],
            'platform' => ['nullable', 'string', 'max:30'],
            'thumbnail' => ['nullable', 'string', 'max:500'],
            'category' => ['nullable', 'string', 'max:255'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
            'is_required' => ['nullable', 'boolean'],
            'is_published' => ['nullable', 'boolean'],
        ]);

        $training->update($data);

        return response()->json(['success' => true, 'data' => new TrainingResource($training)]);
    }

    public function destroy(int $id): JsonResponse
    {
        Training::findOrFail($id)->delete();

        return response()->json(['success' => true]);
    }

    /**
     * Sube una imagen de portada para una capacitación.
     * POST /api/admin/trainings/thumbnail
     *
     * No hay un endpoint de media genérico utilizable por un administrador
     * (todos los existentes exigen ser dueño de una tienda/producto), así
     * que este se resuelve dentro del propio módulo de capacitaciones.
     */
    public function uploadThumbnail(Request $request): JsonResponse
    {
        $request->validate([
            'file' => ['required', 'image', 'mimes:jpeg,png,jpg,webp', 'max:5120'],
        ]);

        $path = $request->file('file')->store('trainings', 'public');

        return response()->json([
            'success' => true,
            'data' => ['url' => Storage::url($path)],
        ]);
    }

    /**
     * GET /api/admin/trainings/progress
     *
     * Devuelve el progreso de capacitaciones por vendedor (store con suscripción activa).
     * Incluye métricas generales y detalle por vendedor.
     */
    public function progress(): JsonResponse
    {
        $totalTrainings = Training::count();
        $totalRequired = Training::where('is_required', true)->count();

        $stores = Store::with([
            'owner:id,name,email',
        ])->get();
        $stores = $stores->filter(fn(Store $s) => $s->owner !== null)->values();

        $allProgress = TrainingProgress::whereIn('store_id', $stores->pluck('id'))
            ->whereNotNull('completed_at')
            ->get()
            ->groupBy('store_id');

        $requiredTrainingIds = Training::where('is_required', true)->pluck('id');

        $data = $stores->map(function (Store $store) use ($allProgress, $requiredTrainingIds, $totalTrainings, $totalRequired): array {
            $completed = collect($allProgress->get($store->id, []));
            $completedIds = $completed->pluck('training_id');
            $completedCount = $completedIds->count();
            $requiredCompleted = $completedIds->intersect($requiredTrainingIds)->count();

            $requiredPending = $requiredTrainingIds->diff($completedIds)->isNotEmpty()
                ? Training::whereIn('id', $requiredTrainingIds->diff($completedIds)->values())
                    ->get(['id', 'title'])
                    ->toArray()
                : [];

            return [
                'store_id' => $store->id,
                'trade_name' => $store->trade_name,
                'seller_name' => $store->owner->name,
                'seller_email' => $store->owner->email,
                'total_trainings' => $totalTrainings,
                'completed_trainings' => $completedCount,
                'required_trainings' => $totalRequired,
                'required_completed' => $requiredCompleted,
                'progress_percent' => $totalTrainings > 0
                    ? (int) round(($completedCount / $totalTrainings) * 100)
                    : 0,
                'required_pending' => $requiredPending,
            ];
        });

        $activeSellers = $data->count();
        $overallCompletion = $activeSellers > 0 && $totalTrainings > 0
            ? (int) round($data->sum('completed_trainings') / ($totalTrainings * $activeSellers) * 100)
            : 0;

        $requiredCompletion = $activeSellers > 0 && $totalRequired > 0
            ? (int) round($data->sum('required_completed') / ($totalRequired * $activeSellers) * 100)
            : 0;

        return response()->json([
            'success' => true,
            'data' => $data,
            'meta' => [
                'total_sellers' => $activeSellers,
                'total_trainings' => $totalTrainings,
                'total_required' => $totalRequired,
                'overall_completion' => $overallCompletion,
                'required_completion' => $requiredCompletion,
            ],
        ]);
    }
}
