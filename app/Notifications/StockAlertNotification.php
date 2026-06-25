<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Channels\PushChannel;
use App\Models\Product;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

final class StockAlertNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;
    public int $backoff = 60;

    public function __construct(
        private readonly Product $product,
        private readonly string $level, // 'low' | 'critical' | 'out'
    ) {}

    public function via(object $notifiable): array
    {
        $channels = ['database', 'mail'];

        $settings = $notifiable->notificationSetting;
        if ($settings?->wantsPush() ?? true) {
            $channels[] = PushChannel::class;
        }

        return $channels;
    }

    public function toPush(object $notifiable): array
    {
        return [
            'title' => match ($this->level) {
                'out'      => '⚠️ Producto agotado',
                'critical' => '🔔 Stock crítico',
                default    => '📦 Stock bajo',
            },
            'body' => match ($this->level) {
                'out'      => "\"{$this->product->name}\" se agotó. Repón stock para seguir vendiéndolo.",
                'critical' => "\"{$this->product->name}\" tiene solo {$this->product->stock} unidad(es). ¡Repón pronto!",
                default    => "\"{$this->product->name}\" tiene {$this->product->stock} unidad(es). Considera reabastecer.",
            },
            'data' => [
                'type'       => 'stock_alert',
                'level'      => $this->level,
                'product_id' => (string) $this->product->id,
                'url'        => '/seller/inventario',
            ],
        ];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $subject = match ($this->level) {
            'out'      => '⚠️ Producto agotado — ' . $this->product->name,
            'critical' => '🔔 Stock crítico — ' . $this->product->name,
            default    => '📦 Stock bajo — ' . $this->product->name,
        };

        return (new MailMessage)
            ->subject($subject)
            ->view('emails.notifications.stock-alert', [
                'sellerName'  => $notifiable->name,
                'productName' => $this->product->name,
                'stock'       => $this->product->stock,
                'level'       => $this->level,
                'actionUrl'   => config('app.frontend_url') . '/seller/inventario',
            ]);
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type'         => 'stock_alert',
            'level'        => $this->level,
            'title'        => match ($this->level) {
                'out'      => "⚠️ Producto agotado: {$this->product->name}",
                'critical' => "🔔 Stock crítico: {$this->product->name}",
                default    => "📦 Stock bajo: {$this->product->name}",
            },
            'message'      => match ($this->level) {
                'out'      => "Tu producto \"{$this->product->name}\" se ha agotado (0 unidades). Repón stock para seguir vendiéndolo.",
                'critical' => "Tu producto \"{$this->product->name}\" tiene solo {$this->product->stock} unidad(es). ¡Repón pronto!",
                default    => "Tu producto \"{$this->product->name}\" tiene {$this->product->stock} unidad(es). Considera reabastecer pronto.",
            },
            'product_id'   => $this->product->id,
            'product_name' => $this->product->name,
            'stock'        => $this->product->stock,
            'action_url'   => '/seller/inventario',
        ];
    }
}
