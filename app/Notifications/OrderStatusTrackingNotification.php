<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Channels\PushChannel;
use App\Models\Order;
use App\Services\CarrierResolver;
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
            'title' => 'TU PEDIDO HA SIDO RECIBIDO',
        ],
        Order::STATUS_CONFIRMED => [
            'image' => 'tracking-stage2.jpg',
            'title' => 'TU PEDIDO HA SIDO VALIDADO POR EL VENDEDOR',
        ],
        Order::STATUS_PROCESSING => [
            'image' => 'tracking-stage3.jpg',
            'title' => 'TU PEDIDO HA SIDO DESPACHADO CON ÉXITO',
        ],
        Order::STATUS_SHIPPED => [
            'image' => 'tracking-stage4.jpg',
            'title' => '¡TU PEDIDO VA EN CAMINO!',
        ],
        Order::STATUS_DELIVERED => [
            'image' => 'tracking-stage5.jpg',
            'title' => '¡RECIBIDO! CONFIRMAMOS LA ENTREGA DE TU PEDIDO',
        ],
        Order::STATUS_CANCELLED => [
            'image' => null,
            'title' => 'TU PEDIDO HA SIDO CANCELADO',
        ],
    ];

    private const STATUS_LABELS = [
        Order::STATUS_PENDING_SELLER => 'Pedido recibido',
        Order::STATUS_CONFIRMED      => 'Validado por el vendedor',
        Order::STATUS_PROCESSING     => 'En preparación / despachado',
        Order::STATUS_SHIPPED        => 'En camino',
        Order::STATUS_DELIVERED      => 'Entregado',
        Order::STATUS_CANCELLED      => 'Cancelado',
    ];

    private const BANNER_TOP = 'banner-top.jpg';
    private const BANNER_BOTTOM = 'banner-bottom.jpg';

    public function __construct(
        private readonly Order $order,
    ) {}

    public function via(object $notifiable): array
    {
        $settings   = $notifiable->notificationSetting;
        $wantsEmail = $settings?->wantsEmailOrder() ?? true;
        $wantsPush  = $settings?->wantsPush() ?? true;

        $channels = ['database'];
        if ($wantsEmail) {
            $channels[] = 'mail';
        }
        if ($wantsPush) {
            $channels[] = PushChannel::class;
        }

        return $channels;
    }

    public function toPush(object $notifiable): array
    {
        $label = self::STATUS_LABELS[$this->order->status] ?? 'Actualizado';

        return [
            'title' => '📦 Pedido actualizado',
            'body'  => "Pedido #{$this->order->order_number}: {$label}",
            'data'  => [
                'type'         => 'order_tracking',
                'order_id'     => (string) $this->order->id,
                'order_number' => (string) $this->order->order_number,
                'status'       => $this->order->status,
                'url'          => '/customer/orders',
            ],
        ];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $status = $this->order->status;
        $tracking = self::TRACKING_MAP[$status] ?? null;

        if (!$tracking) {
            return $this->fallbackMail($notifiable);
        }

        $imagePath = $tracking['image'] ? public_path('images/email/' . $tracking['image']) : null;
        $imageCid = $tracking['image'] ? 'tracking-' . str_replace('_', '-', $status) : null;

        $bannerTopPath = public_path('images/email/' . self::BANNER_TOP);
        $bannerBottomPath = public_path('images/email/' . self::BANNER_BOTTOM);

        $carrierInfo = $this->getCarrierInfo();

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
                'carrierName'        => $carrierInfo['name'],
                'trackingCode'       => $carrierInfo['tracking_code'],
                'trackingUrl'        => $carrierInfo['tracking_url'],
                'carrierFields'      => $carrierInfo['fields'],
            ])
            ->withSymfonyMessage(function ($message) use ($imagePath, $imageCid, $bannerTopPath, $bannerBottomPath) {
                if (file_exists($bannerTopPath)) {
                    $message->embedFromPath($bannerTopPath, 'banner-top');
                }
                if ($imagePath && $imageCid && file_exists($imagePath)) {
                    $message->embedFromPath($imagePath, $imageCid);
                }
                if (file_exists($bannerBottomPath)) {
                    $message->embedFromPath($bannerBottomPath, 'banner-bottom');
                }
            });
    }

    private function fallbackMail(object $notifiable): MailMessage
    {
        $carrierInfo = $this->getCarrierInfo();

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
                'carrierName'     => $carrierInfo['name'],
                'trackingCode'    => $carrierInfo['tracking_code'],
                'trackingUrl'     => $carrierInfo['tracking_url'],
                'carrierFields'   => $carrierInfo['fields'],
            ])
            ->withSymfonyMessage(function ($message) {
                //
            });
    }

    private function getCarrierInfo(): array
    {
        $firstShipment = $this->order->relationLoaded('shipments')
            ? $this->order->shipments->first()
            : $this->order->shipments()->first();

        if ($firstShipment === null) {
            return ['name' => null, 'tracking_code' => null, 'tracking_url' => null, 'fields' => []];
        }

        $carrierData = $firstShipment->carrier_data ?? [];
        $carrierCode = CarrierResolver::resolveFromShipment($firstShipment);
        $carrierConfig = $carrierCode ? config("logistics.carriers.{$carrierCode}") : null;

        $trackingCode = $carrierData['tracking_code'] ?? $firstShipment->tracking_number;
        $trackingUrl = $firstShipment->tracking_url;

        $extraFields = [];
        if ($carrierData && $carrierConfig) {
            foreach ($carrierConfig['fields'] as $field) {
                $key = $field['key'];
                if ($key !== 'tracking_code' && isset($carrierData[$key])) {
                    $extraFields[] = [
                        'label' => $field['label'],
                        'value' => $carrierData[$key],
                    ];
                }
            }
        }

        return [
            'name' => $carrierConfig['name'] ?? ($firstShipment->carrier ?? null),
            'tracking_code' => $trackingCode,
            'tracking_url' => $trackingUrl,
            'fields' => $extraFields,
        ];
    }

    public function toArray(object $notifiable): array
    {
        $label = self::STATUS_LABELS[$this->order->status] ?? 'Actualizado';

        return [
            'type'         => 'order_tracking',
            'order_id'     => $this->order->id,
            'order_number' => $this->order->order_number,
            'status'       => $this->order->status,
            'subject'      => "Pedido #{$this->order->order_number}: {$label}",
        ];
    }
}
