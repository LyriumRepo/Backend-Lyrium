<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Channels\PushChannel;
use App\Models\Review;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

final class NewReviewNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;
    public int $backoff = 60;

    public function __construct(
        private readonly Review $review,
    ) {}

    public function via(object $notifiable): array
    {
        $channels = ['database'];

        $settings = $notifiable->notificationSetting;

        if ($settings?->wantsPush() ?? true) {
            $channels[] = PushChannel::class;
        }

        if ($settings?->wantsEmailOrder() ?? true) {
            $channels[] = 'mail';
        }

        return $channels;
    }

    public function toPush(object $notifiable): array
    {
        $productName = $this->review->product?->name ?? 'tu producto';
        $stars = str_repeat('⭐', $this->review->rating);

        return [
            'title' => '⭐ Nueva reseña recibida',
            'body'  => "{$stars} en \"{$productName}\": \"{$this->getPreview()}\"",
            'data'  => [
                'type'       => 'new_review',
                'product_id' => (string) ($this->review->product_id ?? ''),
                'url'        => '/seller/products',
            ],
        ];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $productName = $this->review->product?->name ?? 'tu producto';
        $stars = str_repeat('⭐', $this->review->rating);

        return (new MailMessage)
            ->subject("{$stars} Nueva reseña en \"{$productName}\" — Lyrium")
            ->greeting('¡Hola, ' . $notifiable->name . '!')
            ->line("Recibiste una nueva reseña en tu producto **{$productName}**.")
            ->line("Calificación: {$stars} ({$this->review->rating}/5)")
            ->when($this->review->title, fn ($m) => $m->line("**Título:** {$this->review->title}"))
            ->when($this->review->comment, fn ($m) => $m->line("**Comentario:** \"{$this->review->comment}\""))
            ->action('Ver reseñas', config('app.frontend_url') . '/seller/products');
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type'         => 'new_review',
            'title'        => '⭐ Nueva reseña en tu producto',
            'message'      => "Calificación {$this->review->rating}/5 en \"{$this->review->product?->name}\": {$this->getPreview()}",
            'review_id'    => $this->review->id,
            'product_id'   => $this->review->product_id,
            'product_name' => $this->review->product?->name ?? '',
            'rating'       => $this->review->rating,
            'comment'      => $this->review->comment,
            'reviewer'     => $this->review->user?->name ?? 'Cliente',
            'action_url'   => '/seller/products',
        ];
    }

    private function getPreview(): string
    {
        $text = $this->review->comment ?? $this->review->title ?? '';
        return mb_strlen($text) > 80 ? mb_substr($text, 0, 80) . '…' : $text;
    }
}
