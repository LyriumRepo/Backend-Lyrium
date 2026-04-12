<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Review\StoreReviewRequest;
use App\Http\Resources\ReviewResource;
use App\Models\Order;
use App\Models\Review;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class ReviewController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $productId = $request->query('product_id');

        if (! $productId) {
            return $this->error('El parámetro product_id es requerido.', 400);
        }

        $reviews = Review::where('product_id', $productId)
            ->with(['user:id,name,avatar'])
            ->orderBy('created_at', 'desc')
            ->paginate(10);

        $stats = Review::getRatingStats((int) $productId);

        return $this->success([
            'data' => ReviewResource::collection($reviews),
            'stats' => $stats,
            'pagination' => [
                'page' => $reviews->currentPage(),
                'perPage' => $reviews->perPage(),
                'total' => $reviews->total(),
                'totalPages' => $reviews->lastPage(),
                'hasMore' => $reviews->hasMorePages(),
            ],
        ]);
    }

    public function store(StoreReviewRequest $request): JsonResponse
    {
        $data = $request->validated();
        $user = $request->user();

        $existingReview = Review::where('user_id', $user->id)
            ->where('product_id', $data['product_id'])
            ->first();

        if ($existingReview) {
            return $this->error('Ya has dejado una reseña para este producto.', 400);
        }

        $isVerifiedPurchase = false;

        if (isset($data['order_id'])) {
            $order = Order::where('id', $data['order_id'])
                ->where('user_id', $user->id)
                ->where('status', 'delivered')
                ->whereHas('items', fn ($q) => $q->where('product_id', $data['product_id']))
                ->first();

            if ($order) {
                $isVerifiedPurchase = true;
            }
        }

        $review = Review::create([
            'user_id' => $user->id,
            'product_id' => $data['product_id'],
            'order_id' => $data['order_id'] ?? null,
            'rating' => $data['rating'],
            'title' => $data['title'] ?? null,
            'comment' => $data['comment'] ?? null,
            'is_verified_purchase' => $isVerifiedPurchase,
        ]);

        $review->load(['user:id,name,avatar']);

        return $this->created(new ReviewResource($review));
    }

    public function show(string $id): JsonResponse
    {
        $review = Review::with(['user:id,name,avatar', 'product:id,name,slug'])->findOrFail($id);

        return $this->success(new ReviewResource($review));
    }

    public function update(StoreReviewRequest $request, string $id): JsonResponse
    {
        $review = Review::findOrFail($id);
        $user = $request->user();

        if ($review->user_id !== $user->id && ! $user->hasRole('administrator')) {
            return $this->forbidden('No tienes permiso para editar esta reseña.');
        }

        $data = $request->validated();

        $review->update($data);

        $review->load(['user:id,name,avatar']);

        return $this->success(new ReviewResource($review));
    }

    public function destroy(Request $request, string $id): JsonResponse
    {
        $review = Review::findOrFail($id);
        $user = $request->user();

        if ($review->user_id !== $user->id && ! $user->hasRole('administrator')) {
            return $this->forbidden('No tienes permiso para eliminar esta reseña.');
        }

        $review->delete();

        return $this->success(['message' => 'Reseña eliminada correctamente.']);
    }
}
