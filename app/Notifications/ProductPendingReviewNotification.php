<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Models\Product;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

final class ProductPendingReviewNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;
    public int $backoff = 60;

    public function __construct(
        private readonly Product $product,
    ) {}

    public function via(object $notifiable): array
    {
        return ['database', 'mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('🆕 Nuevo producto pendiente de revisión — Lyrium BioMarketplace')
            ->view('emails.notifications.product-pending-review', [
                'adminName'   => $notifiable->name,
                'productName' => $this->product->name,
                'storeName'   => $this->product->store?->trade_name ?? 'Tienda desconocida',
                'productType' => $this->product->type ?? 'physical',
                'price'       => $this->product->price,
                'actionUrl'   => config('app.frontend_url') . '/admin/products',
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
        return [
            'type'        => 'product_pending_review',
            'title'       => "🆕 Producto pendiente: {$this->product->name}",
            'message'     => "La tienda \"{$this->product->store?->trade_name ?? 'desconocida'}\" registró el producto \"{$this->product->name}\" y está esperando aprobación.",
            'product_id'  => $this->product->id,
            'product_name' => $this->product->name,
            'store_id'    => $this->product->store_id,
            'store_name'  => $this->product->store?->trade_name ?? '',
            'action_url'  => '/admin/products',
        ];
    }
}
