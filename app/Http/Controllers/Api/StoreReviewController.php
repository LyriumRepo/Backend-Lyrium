<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\StoreReviewResource;
use App\Models\Order;
use App\Models\Store;
use App\Models\StoreReview;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class StoreReviewController extends Controller
{
    /**
     * GET /api/stores/{slug}/reviews
     * Público
     */
    public function index(Request $request, string $slug): JsonResponse
    {
        $store = Store::where('slug', $slug)->firstOrFail();

        $reviews = StoreReview::where('store_id', $store->id)
            ->with(['user:id,name,avatar'])
            ->orderByDesc('created_at')
            ->paginate(10);

        $stats = StoreReview::getStoreStats($store->id);

        return $this->success([
            'data'       => StoreReviewResource::collection($reviews),
            'stats'      => $stats,
            'pagination' => [
                'page'       => $reviews->currentPage(),
                'perPage'    => $reviews->perPage(),
                'total'      => $reviews->total(),
                'totalPages' => $reviews->lastPage(),
                'hasMore'    => $reviews->hasMorePages(),
            ],
        ]);
    }

    /**
     * POST /api/stores/{slug}/reviews
     * Requiere auth
     */
    public function store(Request $request, string $slug): JsonResponse
    {
        $request->validate([
            'rating'               => 'required|integer|min:1|max:5',
            'rating_communication' => 'nullable|integer|min:1|max:5',
            'rating_shipping'      => 'nullable|integer|min:1|max:5',
            'rating_packaging'     => 'nullable|integer|min:1|max:5',
            'title'                => 'nullable|string|max:150',
            'comment'              => 'nullable|string|max:2000',
            'order_id'             => 'nullable|integer|exists:orders,id',
        ], [
            'rating.required' => 'La calificación general es obligatoria.',
            'rating.min'      => 'La calificación mínima es 1 estrella.',
            'rating.max'      => 'La calificación máxima es 5 estrellas.',
        ]);

        $store = Store::where('slug', $slug)->firstOrFail();
        $user  = $request->user();

        // Un usuario solo puede reseñar una tienda una vez
        $alreadyReviewed = StoreReview::where('store_id', $store->id)
            ->where('user_id', $user->id)
            ->exists();

        if ($alreadyReviewed) {
            return $this->error('Ya dejaste una reseña para esta tienda.', 422);
        }

        // Verificar compra en esta tienda
        $isVerifiedPurchase = Order::where('user_id', $user->id)
            ->where('status', 'completed')
            ->whereHas('items', fn($q) => $q->where('store_id', $store->id))
            ->exists();

        $review = StoreReview::create([
            'store_id'             => $store->id,
            'user_id'              => $user->id,
            'order_id'             => $request->order_id,
            'rating'               => $request->rating,
            'rating_communication' => $request->rating_communication,
            'rating_shipping'      => $request->rating_shipping,
            'rating_packaging'     => $request->rating_packaging,
            'title'                => $request->title,
            'comment'              => $request->comment,
            'is_verified_purchase' => $isVerifiedPurchase,
        ]);

        return $this->created(
            new StoreReviewResource($review->load('user:id,name,avatar'))
        );
    }

    /**
     * PUT /api/stores/reviews/{id}
     * Solo el autor o admin
     */
    public function update(Request $request, string $id): JsonResponse
    {
        $review = StoreReview::findOrFail($id);
        $user   = $request->user();

        if ($review->user_id !== $user->id && ! $user->hasRole('administrator')) {
            return $this->forbidden('No tienes permiso para editar esta reseña.');
        }

        $request->validate([
            'rating'               => 'sometimes|integer|min:1|max:5',
            'rating_communication' => 'nullable|integer|min:1|max:5',
            'rating_shipping'      => 'nullable|integer|min:1|max:5',
            'rating_packaging'     => 'nullable|integer|min:1|max:5',
            'title'                => 'nullable|string|max:150',
            'comment'              => 'nullable|string|max:2000',
        ]);

        $review->update($request->only([
            'rating',
            'rating_communication',
            'rating_shipping',
            'rating_packaging',
            'title',
            'comment',
        ]));

        return $this->success(
            new StoreReviewResource($review->load('user:id,name,avatar'))
        );
    }

    /**
     * DELETE /api/stores/reviews/{id}
     * Solo el autor o admin
     */
    public function destroy(Request $request, string $id): JsonResponse
    {
        $review = StoreReview::findOrFail($id);
        $user   = $request->user();

        if ($review->user_id !== $user->id && ! $user->hasRole('administrator')) {
            return $this->forbidden('No tienes permiso para eliminar esta reseña.');
        }

        $review->delete();

        return $this->success(['message' => 'Reseña eliminada correctamente.']);
    }
}
