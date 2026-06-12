<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\ReviewReportResource;
use App\Http\Resources\ReviewResource;
use App\Models\Review;
use App\Models\ReviewReport;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

final class ReviewModerationController extends Controller
{
    /**
     * GET /api/admin/reviews?status=pending&search=X&per_page=20
     * Todas las reseñas con filtros
     */
    public function index(Request $request): JsonResponse
    {
        $query = Review::with(['user:id,name', 'product:id,name,slug'])
            ->withCount('reports as reports_count');

        if ($search = $request->query('search')) {
            $query->where(
                fn ($q) => $q->where('comment', 'like', "%{$search}%")
                    ->orWhere('title', 'like', "%{$search}%")
            );
        }

        if ($rating = $request->query('rating')) {
            $query->where('rating', (int) $rating);
        }

        if ($request->boolean('reported')) {
            $query->where('reported_count', '>', 0);
        }

        $perPage = min((int) $request->query('per_page', 20), 100);
        $reviews = $query->orderByDesc('created_at')->paginate($perPage);

        return response()->json([
            'success' => true,
            'data' => ReviewResource::collection($reviews),
            'meta' => [
                'current_page' => $reviews->currentPage(),
                'per_page' => $reviews->perPage(),
                'total' => $reviews->total(),
                'total_pages' => $reviews->lastPage(),
            ],
        ]);
    }

    /**
     * GET /api/admin/reviews/reported?status=pending
     * Reseñas reportadas para moderación
     */
    public function reported(Request $request): JsonResponse
    {
        $status = $request->query('status', 'pending');
        $perPage = min((int) $request->query('per_page', 20), 100);

        $reports = ReviewReport::with([
            'review.user:id,name',
            'review.product:id,name,slug',
            'reporter:id,name',
            'moderator:id,name',
        ])
            ->when(
                in_array($status, ['pending', 'accepted', 'dismissed'], true),
                fn ($q) => $q->where('status', $status)
            )
            ->orderByDesc('created_at')
            ->paginate($perPage);

        return response()->json([
            'success' => true,
            'data' => ReviewReportResource::collection($reports),
            'meta' => [
                'current_page' => $reports->currentPage(),
                'per_page' => $reports->perPage(),
                'total' => $reports->total(),
                'total_pages' => $reports->lastPage(),
            ],
        ]);
    }

    /**
     * PUT /api/admin/reviews/{id}/moderate
     * Moderar una reseña: aprobar reporte (elimina reseña) o desestimar
     */
    public function moderate(Request $request, string $id): JsonResponse
    {
        $validated = $request->validate([
            'action' => 'required|string|in:accept,dismiss',
            'report_id' => 'nullable|integer|exists:review_reports,id',
        ]);

        $review = Review::findOrFail($id);
        $admin = $request->user();

        DB::transaction(function () use ($review, $validated, $admin) {
            $newStatus = $validated['action'] === 'accept' ? 'accepted' : 'dismissed';

            // Actualizar reporte específico o todos los pendientes de esa reseña
            $reportQuery = ReviewReport::where('review_id', $review->id)
                ->where('status', 'pending');

            if (! empty($validated['report_id'])) {
                $reportQuery->where('id', $validated['report_id']);
            }

            $reportQuery->update([
                'status' => $newStatus,
                'reviewed_by' => $admin->id,
                'reviewed_at' => now(),
            ]);

            if ($validated['action'] === 'accept') {
                // Reporte aceptado → eliminar la reseña
                $review->delete();
            } else {
                // Reporte desestimado → limpiar el contador
                $review->update(['reported_count' => 0]);
            }
        });

        return response()->json([
            'success' => true,
            'message' => $validated['action'] === 'accept'
                ? 'Reseña eliminada correctamente.'
                : 'Reporte desestimado. La reseña se mantiene.',
        ]);
    }

    /**
     * DELETE /api/admin/reviews/{id}
     * Eliminar reseña directamente sin reporte
     */
    public function destroy(string $id): JsonResponse
    {
        Review::findOrFail($id)->delete();

        return response()->json(['success' => true, 'message' => 'Reseña eliminada.']);
    }
}
