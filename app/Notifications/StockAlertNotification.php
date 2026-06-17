<?php

declare(strict_types=1);

namespace App\Notifications;

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
        private readonly string $level, // 'critical' | 'out'
    ) {}

    public function via(object $notifiable): array
    {
        return ['database', 'mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $isOut = $this->level === 'out';

        return (new MailMessage)
            ->subject($isOut
                ? '⚠️ Producto agotado — ' . $this->product->name
                : '🔔 Stock crítico — ' . $this->product->name
            )
            ->view('emails.notifications.stock-alert', [
                'sellerName'  => $notifiable->name,
                'productName' => $this->product->name,
                'stock'       => $this->product->stock,
                'level'       => $this->level,
                'actionUrl'   => config('app.frontend_url') . '/seller/inventario',
            ])
            ->withSymfonyMessage(function ($message) {
                $iconPath = public_path('images/iconologo.png');
                $textPath = public_path('images/nombrelogo.png');
                if (file_exists($iconPath)) {
                    $message->embedFromPath($iconPath, 'logo-icon');
                }
                if (file_exists($textPath)) {
                    $message->embedFromPath($textPath, 'logo-text');
                }
            });
    }

    public function toArray(object $notifiable): array
    {
        $isOut = $this->level === 'out';

        return [
            'type'         => 'stock_alert',
            'level'        => $this->level,
            'title'        => $isOut
                ? "⚠️ Producto agotado: {$this->product->name}"
                : "🔔 Stock crítico: {$this->product->name}",
            'message'      => $isOut
                ? "Tu producto \"{$this->product->name}\" se ha agotado (0 unidades). Repón stock para seguir vendiéndolo."
                : "Tu producto \"{$this->product->name}\" tiene solo {$this->product->stock} unidad(es) disponible(s). Considera reabastecer pronto.",
            'product_id'   => $this->product->id,
            'product_name' => $this->product->name,
            'stock'        => $this->product->stock,
            'action_url'   => '/seller/inventario',
        ];
    }
}
