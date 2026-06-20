<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Channels\PushChannel;
use App\Models\Product;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

final class ProductStatusNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;
    public int $backoff = 60;


    public function __construct(
        private readonly Product $product,
        private readonly string $newStatus,
        private readonly ?string $reason = null,
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
        $storeName = $this->product->store?->trade_name ?? 'tu tienda';

        return (new MailMessage)
            ->subject(match ($this->newStatus) {
                'approved' => '✅ Tu producto fue aprobado — Lyrium BioMarketplace',
                'rejected' => '❌ Tu producto no fue aprobado — Lyrium BioMarketplace',
                default    => 'Actualización de tu producto — Lyrium BioMarketplace',
            })
            ->view('emails.notifications.product-status', [
                'name'        => $notifiable->name,
                'productName' => $this->product->name,
                'storeName'   => $storeName,
                'status'      => $this->newStatus,
                'reason'      => $this->reason,
                'actionUrl'   => config('app.frontend_url') . '/seller/products',
            ]);
    }

    public function toPush(object $notifiable): array
    {
        $label = $this->newStatus === 'approved' ? 'aprobado ✅' : 'rechazado ❌';
        return [
            'title' => 'Producto ' . $label,
            'body'  => "Tu producto \"{$this->product->name}\" fue {$label}." .
                       ($this->reason ? " Motivo: {$this->reason}" : ''),
            'data'  => [
                'type'       => 'product_status_changed',
                'product_id' => (string) $this->product->id,
                'status'     => $this->newStatus,
                'url'        => '/seller/products',
            ],
        ];
    }

    public function toArray(object $notifiable): array
    {
        $label = $this->newStatus === 'approved' ? 'aprobado' : 'rechazado';
        return [
            'type'         => 'product_status_changed',
            'product_id'   => $this->product->id,
            'product_name' => $this->product->name,
            'status'       => $this->newStatus,
            'reason'       => $this->reason,
            'subject'      => "Tu producto \"{$this->product->name}\" fue {$label}" .
                              ($this->reason ? " — {$this->reason}" : ''),
        ];
    }
}
