<?php

declare(strict_types=1);

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

final class NewBlogContentForReview extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        private readonly string $message,
        private readonly string $contentType,
        private readonly int $contentId,
        private readonly string $storeName,
    ) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        return [
            'message' => $this->message,
            'action' => 'pending_review',
            'content_type' => $this->contentType,
            'content_id' => $this->contentId,
            'store_name' => $this->storeName,
            'type' => 'new_blog_content_for_review',
        ];
    }
}
