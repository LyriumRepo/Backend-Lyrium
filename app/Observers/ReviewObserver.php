<?php

namespace App\Observers;

use App\Models\Product;
use App\Models\Review;
use App\Notifications\NewReviewNotification;

class ReviewObserver
{
    public function created(Review $review): void
    {
        $this->recalcularProducto($review->product_id);
        $this->notifyStoreOwner($review);
    }

    /**
     * Se dispara cuando se actualiza una reseña (ej. edición del rating).
     */
    public function updated(Review $review): void
    {
        $this->recalcularProducto($review->product_id);

        // Si el product_id cambió (caso edge), recalcular el producto anterior también
        if ($review->wasChanged('product_id')) {
            $this->recalcularProducto($review->getOriginal('product_id'));
        }
    }

    /**
     * Se dispara cuando se elimina una reseña (soft delete no aplica aquí
     * porque la tabla reviews no tiene deleted_at).
     */
    public function deleted(Review $review): void
    {
        $this->recalcularProducto($review->product_id);
    }

    /**
     * Recalcula rating_promedio y rating_count de un producto
     * a partir de todas sus reseñas existentes.
     */
    private function notifyStoreOwner(Review $review): void
    {
        $owner = $review->product?->store?->owner;
        if ($owner) {
            $owner->notify(new NewReviewNotification($review->load(['product.store', 'user'])));
        }
    }

    private function recalcularProducto(int $productId): void
    {
        $stats = Review::where('product_id', $productId)
            ->selectRaw('COUNT(*) as total, COALESCE(AVG(rating), 0) as promedio')
            ->first();

        Product::where('id', $productId)->update([
            'rating_count' => (int) $stats->total,
            'rating_promedio' => round((float) $stats->promedio, 2),
        ]);
    }
}
