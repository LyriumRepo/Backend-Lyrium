<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Channels\PushChannel;
use App\Models\Order;
use App\Models\Store;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

final class CommissionGeneratedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        private readonly Order $order,
        private readonly Store $store,
        private readonly float $grossAmount,
        private readonly float $commissionRate,
        private readonly float $commissionAmount,
        private readonly float $netAmount,
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
        return [
            'title' => '💰 Venta procesada',
            'body'  => sprintf(
                'Pedido #%s: recibirás S/ %.2f (comisión %.0f%% descontada).',
                $this->order->order_number,
                $this->netAmount,
                $this->commissionRate
            ),
            'data' => [
                'type'     => 'commission_generated',
                'order_id' => (string) $this->order->id,
                'url'      => '/seller/finance',
            ],
        ];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject("💰 Venta procesada — Pedido #{$this->order->order_number}")
            ->greeting('¡Hola, ' . $notifiable->name . '!')
            ->line("Se ha procesado una venta en tu tienda **{$this->store->trade_name}**.")
            ->line("**Pedido:** #{$this->order->order_number}")
            ->line(sprintf('**Monto bruto:** S/ %.2f', $this->grossAmount))
            ->line(sprintf('**Comisión Lyrium (%.0f%%):** S/ %.2f', $this->commissionRate, $this->commissionAmount))
            ->line(sprintf('**Monto neto a recibir:** S/ %.2f', $this->netAmount))
            ->action('Ver mis finanzas', config('app.frontend_url') . '/seller/finance');
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type'              => 'commission_generated',
            'title'             => '💰 Venta procesada',
            'message'           => sprintf(
                'Pedido #%s: monto bruto S/ %.2f, comisión %.0f%%, neto S/ %.2f.',
                $this->order->order_number,
                $this->grossAmount,
                $this->commissionRate,
                $this->netAmount
            ),
            'order_id'          => $this->order->id,
            'order_number'      => $this->order->order_number,
            'store_id'          => $this->store->id,
            'gross_amount'      => $this->grossAmount,
            'commission_rate'   => $this->commissionRate,
            'commission_amount' => $this->commissionAmount,
            'net_amount'        => $this->netAmount,
            'action_url'        => '/seller/finance',
        ];
    }
}
