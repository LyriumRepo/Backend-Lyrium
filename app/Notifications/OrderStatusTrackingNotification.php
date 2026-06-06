<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Models\Order;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

final class OrderStatusTrackingNotification extends Notification implements ShouldQueue
{
    use Queueable;

    private const TRACKING_MAP = [
        Order::STATUS_PENDING_SELLER => [
            'image' => 'tracking-stage1.jpg',
            'title' => 'TU PEDIDO HA SIDO VALIDADO POR EL VENDEDOR',
        ],
        Order::STATUS_CONFIRMED => [
            'image' => 'tracking-stage2.jpg',
            'title' => 'TU PEDIDO HA SIDO DESPACHADO CON ÉXITO',
        ],
        Order::STATUS_PROCESSING => [
            'image' => 'tracking-stage3.jpg',
            'title' => '¡TU PEDIDO VA EN CAMINO!',
        ],
        Order::STATUS_SHIPPED => [
            'image' => 'tracking-stage4.jpg',
            'title' => '¡YA LLEGAMOS! REPARTIDOR EN TU DOMICILIO',
        ],
        Order::STATUS_DELIVERED => [
            'image' => 'tracking-stage5.jpg',
            'title' => '¡RECIBIDO! CONFIRMAMOS LA ENTREGA DE TU PEDIDO',
        ],
    ];

    private const BANNER_TOP = 'banner-top.jpg';
    private const BANNER_BOTTOM = 'banner-bottom.jpg';

    public function __construct(
        private readonly Order $order,
    ) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $status = $this->order->status;
        $tracking = self::TRACKING_MAP[$status] ?? null;

        if (!$tracking) {
            return $this->fallbackMail($notifiable);
        }

        $imagePath = public_path('images/email/' . $tracking['image']);
        $imageCid = 'tracking-' . str_replace('_', '-', $status);

        $bannerTopPath = public_path('images/email/' . self::BANNER_TOP);
        $bannerBottomPath = public_path('images/email/' . self::BANNER_BOTTOM);

        $items = $this->order->items->map(fn($item) => [
            'name' => $item->product_name,
            'quantity' => $item->quantity,
            'line_total' => number_format((float) $item->line_total, 2),
        ])->toArray();

        return (new MailMessage)
            ->subject('Seguimiento de tu pedido #' . $this->order->order_number . ' - Lyrium')
            ->view('emails.notifications.order-tracking', [
                'customerName'       => $notifiable->name,
                'orderNumber'        => $this->order->order_number,
                'trackingTitle'      => $tracking['title'],
                'imageCid'           => $imageCid,
                'bannerTopCid'       => 'banner-top',
                'bannerBottomCid'    => 'banner-bottom',
                'items'              => $items,
                'subtotal'           => number_format((float) $this->order->subtotal, 2),
                'shippingCost'       => number_format((float) $this->order->shipping_cost, 2),
                'total'              => number_format((float) $this->order->total, 2),
                'actionUrl'          => config('app.frontend_url') . '/customer/orders',
                'showTagline'        => true,
                'hideHeader'         => true,
            ])
            ->withSymfonyMessage(function ($message) use ($imagePath, $imageCid, $bannerTopPath, $bannerBottomPath) {
                if (file_exists($bannerTopPath)) {
                    $message->embedFromPath($bannerTopPath, 'banner-top');
                }
                if (file_exists($imagePath)) {
                    $message->embedFromPath($imagePath, $imageCid);
                }
                if (file_exists($bannerBottomPath)) {
                    $message->embedFromPath($bannerBottomPath, 'banner-bottom');
                }
            });
    }

    private function fallbackMail(object $notifiable): MailMessage
    {
        $items = $this->order->items->map(fn($item) => [
            'name' => $item->product_name,
            'quantity' => $item->quantity,
            'line_total' => number_format((float) $item->line_total, 2),
        ])->toArray();

        return (new MailMessage)
            ->subject('Actualizaci\u00f3n de tu pedido #' . $this->order->order_number . ' - Lyrium')
            ->view('emails.notifications.order-tracking', [
                'customerName'    => $notifiable->name,
                'orderNumber'     => $this->order->order_number,
                'trackingTitle'   => 'Tu pedido ha sido actualizado',
                'imageCid'        => null,
                'bannerTopCid'    => null,
                'bannerBottomCid' => null,
                'items'           => $items,
                'subtotal'        => number_format((float) $this->order->subtotal, 2),
                'shippingCost'    => number_format((float) $this->order->shipping_cost, 2),
                'total'           => number_format((float) $this->order->total, 2),
                'actionUrl'       => config('app.frontend_url') . '/customer/orders',
                'showTagline'     => true,
                'hideHeader'      => true,
            ])
            ->withSymfonyMessage(function ($message) {
                //
            });
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type'         => 'order_tracking',
            'order_id'     => $this->order->id,
            'order_number' => $this->order->order_number,
            'status'       => $this->order->status,
            'subject'      => 'Seguimiento de tu pedido #' . $this->order->order_number,
        ];
    }
}
