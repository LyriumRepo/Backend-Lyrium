<?php

declare(strict_types=1);

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

final class BlogContentStatusChanged extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        private readonly string $message,
        private readonly string $action,
        private readonly string $contentType,
        private readonly int $contentId,
    ) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        return [
            'message' => $this->message,
            'action' => $this->action,
            'content_type' => $this->contentType,
            'content_id' => $this->contentId,
            'type' => 'blog_content_status_changed',
        ];
    }
}
