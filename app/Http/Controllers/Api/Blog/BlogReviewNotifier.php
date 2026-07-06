<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Blog;

use App\Models\User;
use App\Notifications\NewBlogContentForReview;

trait BlogReviewNotifier
{
    private function notifyAdminsOnPendingReview(object $model, string $typeLabel): void
    {
        if ($model->status !== 'pending_review') return;

        $store = $model->store ?? null;
        $storeName = $store->name ?? 'Tienda desconocida';
        $title = $model->title ?? 'Sin título';

        $admins = User::role('administrator')->get();
        foreach ($admins as $admin) {
            $admin->notify(new NewBlogContentForReview(
                message: "Nuevo {$typeLabel} pendiente: \"{$title}\" de {$storeName}",
                contentType: $typeLabel,
                contentId: (int) $model->id,
                storeName: $storeName,
            ));
        }
    }
}
