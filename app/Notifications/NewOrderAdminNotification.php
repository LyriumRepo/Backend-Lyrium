<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Channels\PushChannel;
use App\Models\Order;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

final class NewOrderAdminNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;
    public int $backoff = 60;

    public function __construct(private readonly Order $order) {}

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
        return [
            'title' => '🆕 Nueva orden en la plataforma',
            'body'  => "Orden #{$this->order->order_number} por S/ " . number_format((float) $this->order->total, 2),
            'data'  => [
                'type'     => 'new_order_admin',
                'order_id' => $this->order->id,
                'url'      => '/admin/orders/' . $this->order->id,
            ],
        ];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('🆕 Nueva orden — #' . $this->order->order_number)
            ->greeting('Hola, ' . $notifiable->name)
            ->line("Se recibió una nueva orden **#{$this->order->order_number}** por S/ " . number_format((float) $this->order->total, 2) . '.')
            ->action('Ver orden', config('app.frontend_url') . '/admin/orders/' . $this->order->id);
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type'          => 'new_order_admin',
            'title'         => "🆕 Nueva orden #" . $this->order->order_number,
            'message'       => "Nueva orden de {$this->order->shipping_name} por S/ " . number_format((float) $this->order->total, 2),
            'order_id'      => $this->order->id,
            'order_number'  => $this->order->order_number,
            'total'         => $this->order->total,
            'customer_name' => $this->order->shipping_name,
            'action_url'    => '/admin/orders/' . $this->order->id,
        ];
    }
}
