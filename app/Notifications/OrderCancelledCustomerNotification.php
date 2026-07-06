<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Channels\PushChannel;
use App\Models\Order;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

final class OrderCancelledCustomerNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        private readonly Order $order,
    ) {}

    public function via(object $notifiable): array
    {
        $channels = ['database'];
        $settings = $notifiable->notificationSetting;
        if ($settings?->wantsEmailOrder() ?? true) {
            $channels[] = 'mail';
        }
        if ($settings?->wantsPush() ?? true) {
            $channels[] = PushChannel::class;
        }
        return $channels;
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Tu pedido #' . $this->order->order_number . ' fue cancelado — Lyrium')
            ->greeting('Hola, ' . $notifiable->name)
            ->line('Lamentamos informarte que tu pedido **#' . $this->order->order_number . '** ha sido cancelado.')
            ->line('**Total del pedido:** S/ ' . number_format((float) $this->order->total, 2))
            ->when(
                $this->order->payment_status === Order::PAYMENT_STATUS_PAID,
                fn ($m) => $m->line('Si realizaste un pago, el reembolso será procesado en los próximos días hábiles.')
            )
            ->action('Ver mis pedidos', config('app.frontend_url') . '/customer/orders')
            ->line('Si tienes preguntas, puedes contactarnos a través de soporte.');
    }

    public function toPush(object $notifiable): array
    {
        return [
            'title' => '❌ Pedido cancelado',
            'body'  => "Tu pedido #{$this->order->order_number} ha sido cancelado.",
            'data'  => [
                'type'         => 'order_cancelled_customer',
                'order_id'     => (string) $this->order->id,
                'order_number' => (string) $this->order->order_number,
                'url'          => '/customer/orders',
            ],
        ];
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type'         => 'order_cancelled_customer',
            'title'        => '❌ Pedido cancelado',
            'message'      => "Tu pedido #{$this->order->order_number} fue cancelado.",
            'order_id'     => $this->order->id,
            'order_number' => $this->order->order_number,
        ];
    }
}
