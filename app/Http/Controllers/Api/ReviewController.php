<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Review\StoreReviewRequest;
use App\Http\Requests\Review\UpdateReviewRequest;
use App\Http\Resources\ReviewResource;
use App\Models\Order;
use App\Models\Review;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\ReviewReport;

final class ReviewController extends Controller
{
    /**
     * GET /api/reviews?product_id=X
     */
    public function index(Request $request): JsonResponse
    {
        $productId = (int) $request->query('product_id');

        if (! $productId) {
            return $this->error('El parámetro product_id es requerido.', 400);
        }

        $reviews = Review::where('product_id', $productId)
            ->with(['user:id,name,avatar'])
            ->orderByDesc('created_at')
            ->paginate(10);

        $stats = Review::getRatingStats($productId);

        return $this->success([
            'data'       => ReviewResource::collection($reviews),
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
     * POST /api/reviews
     */
    public function store(StoreReviewRequest $request): JsonResponse
    {
        $data = $request->validated();
        $user = $request->user();

        $alreadyReviewed = Review::where('user_id', $user->id)
            ->where('product_id', $data['product_id'])
            ->exists();

        if ($alreadyReviewed) {
            return $this->error('Ya has dejado una reseña para este producto.', 422);
        }

        $isVerifiedPurchase = false;

        if (! empty($data['order_id'])) {
            $isVerifiedPurchase = Order::where('id', $data['order_id'])
                ->where('user_id', $user->id)
                ->where('status', 'delivered')
                ->whereHas('items', fn($q) => $q->where('product_id', $data['product_id']))
                ->exists();  // exists() en vez de first() — no necesitamos el objeto
        }

        $review = Review::create([
            'user_id'              => $user->id,
            'product_id'           => $data['product_id'],
            'order_id'             => $data['order_id'] ?? null,
            'rating'               => $data['rating'],
            'title'                => $data['title'] ?? null,
            'comment'              => $data['comment'] ?? null,
            'is_verified_purchase' => $isVerifiedPurchase,
        ]);

        return $this->created(new ReviewResource($review->load('user:id,name,avatar')));
    }

    /**
     * GET /api/reviews/{id}
     */
    public function show(string $id): JsonResponse
    {
        $review = Review::with([
            'user:id,name,avatar',
            'product:id,name,slug',
        ])->findOrFail($id);

        return $this->success(new ReviewResource($review));
    }

    /**
     * PUT /api/reviews/{id}
     * Solo rating, title y comment — product_id y order_id no cambian
     */
    public function update(UpdateReviewRequest $request, string $id): JsonResponse
    {
        $review = Review::findOrFail($id);
        $user   = $request->user();

        if ($review->user_id !== $user->id && ! $user->hasRole('administrator')) {
            return $this->forbidden('No tienes permiso para editar esta reseña.');
        }

        $review->update($request->validated());
        $review->load('user:id,name,avatar');

        return $this->success(new ReviewResource($review));
    }

    /**
     * DELETE /api/reviews/{id}
     */
    public function destroy(Request $request, string $id): JsonResponse
    {
        $review = Review::findOrFail($id);
        $user   = $request->user();

        if ($review->user_id !== $user->id && ! $user->hasRole('administrator')) {
            return $this->forbidden('No tienes permiso para eliminar esta reseña.');
        }

        $review->delete();

        return $this->success(['message' => 'Reseña eliminada correctamente.']);
    }

    // Agregar este método al ReviewController existente

    /**
     * POST /api/reviews/{id}/report
     * Usuario reporta una reseña
     */
    public function report(Request $request, string $id): JsonResponse
    {
        $request->validate([
            'reason'  => 'required|string|in:spam,offensive,fake,irrelevant,other',
            'details' => 'nullable|string|max:500',
        ]);

        $review = Review::findOrFail($id);
        $user   = $request->user();

        // No puede reportar su propia reseña
        if ($review->user_id === $user->id) {
            return $this->error('No puedes reportar tu propia reseña.', 422);
        }

        // Ya reportó esta reseña
        if ($review->isReportedBy($user->id)) {
            return $this->error('Ya reportaste esta reseña.', 422);
        }

        DB::transaction(function () use ($review, $request, $user) {
            ReviewReport::create([
                'review_id'   => $review->id,
                'reporter_id' => $user->id,
                'reason'      => $request->reason,
                'details'     => $request->details,
                'status'      => 'pending',
            ]);

            // Incrementar contador en la reseña
            $review->increment('reported_count');
        });

        return $this->success(['message' => 'Reseña reportada. La revisaremos pronto.']);
    }
}
