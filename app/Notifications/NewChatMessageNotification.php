<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Channels\PushChannel;
use App\Models\Conversation;
use App\Models\ConversationMessage;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

final class NewChatMessageNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        private readonly ConversationMessage $message,
        private readonly Conversation $conversation,
    ) {}

    public function via(object $notifiable): array
    {
        $channels = ['database'];

        $settings = $notifiable->notificationSetting;
        if ($settings?->wantsPush() ?? true) {
            $channels[] = PushChannel::class;
        }

        return $channels;
    }

    public function toPush(object $notifiable): array
    {
        $senderName = $this->message->sender?->name ?? 'Un cliente';
        $preview = mb_strimwidth($this->message->content ?? '', 0, 80, '…');

        return [
            'title' => "💬 {$senderName}",
            'body' => $preview,
            'data' => [
                'type' => 'new_chat_message',
                'conversation_id' => (string) $this->conversation->id,
                'store_id' => (string) $this->conversation->store_id,
            ],
        ];
    }

    public function toArray(object $notifiable): array
    {
        $senderName = $this->message->sender?->name ?? 'Un cliente';
        $preview = mb_strimwidth($this->message->content ?? '', 0, 120, '…');

        return [
            'type' => 'new_chat_message',
            'conversation_id' => $this->conversation->id,
            'store_id' => $this->conversation->store_id,
            'sender_name' => $senderName,
            'message_preview' => $preview,
            'subject' => "Nuevo mensaje de {$senderName}: {$preview}",
        ];
    }
}
