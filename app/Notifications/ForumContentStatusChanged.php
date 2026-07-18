<?php

declare(strict_types=1);

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

final class ForumContentStatusChanged extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        private readonly string $message,
        private readonly string $action,
        private readonly int $topicId,
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
            'content_type' => 'forum_topic',
            'content_id' => $this->topicId,
            'type' => 'forum_content_status_changed',
        ];
    }
}
