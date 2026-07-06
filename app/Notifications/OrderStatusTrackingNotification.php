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
        Order::STATUS_CONFIRMED => [
            'image' => 'tracking-stage1.jpg',
            'title' => 'TU PEDIDO HA SIDO VALIDADO POR EL VENDEDOR',
        ],
        Order::STATUS_PROCESSING => [
            'image' => 'tracking-stage2.jpg',
            'title' => 'TU PEDIDO HA SIDO DESPACHADO CON ÉXITO',
        ],
        Order::STATUS_SHIPPED => [
            'image' => 'tracking-stage3.jpg',
            'title' => '¡TU PEDIDO VA EN CAMINO!',
        ],
        Order::STATUS_ON_THE_WAY => [
            'image' => 'tracking-stage3.jpg',
            'title' => null, // resuelto dinámicamente según shipping_type
        ],
        Order::STATUS_DELIVERED => [
            'image' => 'tracking-stage4.jpg',
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
        Order::STATUS_SHIPPED        => 'En camino (transporte)',
        Order::STATUS_ON_THE_WAY     => 'Listo para recojo / en camino a domicilio',
        Order::STATUS_DELIVERED      => 'Entregado',
        Order::STATUS_CANCELLED      => 'Cancelado',
    ];

    // Textos para pedidos 100% servicio (sin productos físicos que envíar/despachar)
    private const SERVICE_TRACKING_MAP = [
        Order::STATUS_CONFIRMED   => 'TU SERVICIO HA SIDO VALIDADO',
        Order::STATUS_ON_THE_WAY  => '¡EL ESPECIALISTA VA EN CAMINO!',
        Order::STATUS_DELIVERED   => '¡TU ATENCIÓN HA SIDO COMPLETADA!',
        Order::STATUS_CANCELLED   => 'TU SERVICIO HA SIDO CANCELADO',
    ];

    private const SERVICE_STATUS_LABELS = [
        Order::STATUS_PENDING_SELLER => 'Reserva recibida',
        Order::STATUS_CONFIRMED      => 'Validado por el centro de salud',
        Order::STATUS_ON_THE_WAY     => 'Especialista en camino / atención en curso',
        Order::STATUS_DELIVERED      => 'Atención completada',
        Order::STATUS_CANCELLED      => 'Cancelado',
    ];

    public function __construct(
        private readonly Order $order,
    ) {}

    // Pedido 100% servicio: no hay nada físico que despachar/enviar, por lo que
    // el stepper y los textos de "despacho/envío" del flujo de productos no aplican.
    private function isServiceOnly(): bool
    {
        return $this->order->items->isEmpty() && $this->order->serviceItems->isNotEmpty();
    }

    private function combinedItems(): array
    {
        $products = $this->order->items->map(fn ($item) => [
            'name' => $item->product_name,
            'quantity' => $item->quantity,
            'line_total' => number_format((float) $item->line_total, 2),
        ]);

        $services = $this->order->serviceItems->map(fn ($item) => [
            'name' => $item->service_name,
            'quantity' => $item->quantity,
            'line_total' => number_format((float) $item->line_total, 2),
        ]);

        return $products->concat($services)->toArray();
    }

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
        if ($this->isServiceOnly()) {
            $label = self::SERVICE_STATUS_LABELS[$this->order->status] ?? 'Actualizado';
        } elseif ($this->order->status === Order::STATUS_ON_THE_WAY) {
            $shippingType = $this->order->shipping_type ?? 'delivery';
            $label = in_array($shippingType, self::PICKUP_SHIPPING_TYPES, true)
                ? 'Listo para recojo en sucursal'
                : 'En camino a tu domicilio';
        } else {
            $label = self::STATUS_LABELS[$this->order->status] ?? 'Actualizado';
        }

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

    private const PICKUP_SHIPPING_TYPES = ['pickup', 'service_store'];

    private function resolveTrackingTitle(): ?string
    {
        if ($this->isServiceOnly()) {
            return self::SERVICE_TRACKING_MAP[$this->order->status] ?? null;
        }

        if ($this->order->status !== Order::STATUS_ON_THE_WAY) {
            return self::TRACKING_MAP[$this->order->status]['title'] ?? null;
        }

        $shippingType = $this->order->shipping_type ?? 'delivery';

        return in_array($shippingType, self::PICKUP_SHIPPING_TYPES, true)
            ? '¡TU PEDIDO ESTÁ LISTO PARA RECOJO EN SUCURSAL!'
            : '¡TU PEDIDO ESTÁ EN CAMINO A TU DOMICILIO!';
    }

    public function toMail(object $notifiable): MailMessage
    {
        $status = $this->order->status;
        $isServiceOnly = $this->isServiceOnly();
        $tracking = self::TRACKING_MAP[$status] ?? null;

        if (!$tracking && !$isServiceOnly) {
            return $this->fallbackMail($notifiable);
        }

        $trackingTitle = $this->resolveTrackingTitle();
        if (!$trackingTitle && !$isServiceOnly) {
            return $this->fallbackMail($notifiable);
        }

        $carrierInfo = $this->getCarrierInfo();

        // Los pedidos 100% servicio no tienen nada que despachar/transportar,
        // por lo que no se usa la imagen de etapa del flujo de productos.
        $stageImage = $isServiceOnly ? null : ($tracking['image'] ?? null);

        $items = $this->combinedItems();

        return (new MailMessage)
            ->subject('Seguimiento de tu pedido #' . $this->order->order_number . ' - Lyrium')
            ->withSymfonyMessage(fn ($message) => $this->embedTrackingImages($message, $stageImage))
            ->view('emails.notifications.order-tracking', [
                'customerName'       => $notifiable->name,
                'orderNumber'        => $this->order->order_number,
                'trackingTitle'      => $trackingTitle ?? ($isServiceOnly ? 'Tu servicio ha sido actualizado' : ($tracking['title'] ?? 'Tu pedido ha sido actualizado')),
                'bannerTopCid'       => 'banner-top',
                'bannerBottomCid'    => 'banner-bottom',
                'imageCid'           => $stageImage ? 'tracking-stage' : null,
                'items'              => $items,
                'subtotal'           => number_format((float) $this->order->subtotal, 2),
                'shippingCost'       => number_format((float) $this->order->shipping_cost, 2),
                'total'              => number_format((float) $this->order->total, 2),
                'actionUrl'          => config('app.frontend_url') . '/customer/orders',
                'showTagline'        => true,
                'hideHeader'         => true,
                'hideFooter'         => true,
                'carrierName'        => $carrierInfo['name'],
                'trackingCode'       => $carrierInfo['tracking_code'],
                'trackingUrl'        => $carrierInfo['tracking_url'],
                'carrierFields'      => $carrierInfo['fields'],
            ]);
    }

    /**
     * Embebe los banners fijos (top/bottom) y la imagen de la etapa de seguimiento
     * (si aplica) como adjuntos inline con Content-ID, para que el blade los
     * referencie via cid:banner-top, cid:banner-bottom, cid:tracking-stage.
     */
    private function embedTrackingImages(\Symfony\Component\Mime\Email $message, ?string $stageImage): void
    {
        $bannerTop = public_path('images/email/banner-top.jpg');
        $bannerBottom = public_path('images/email/banner-bottom.jpg');

        if (file_exists($bannerTop)) {
            $message->embedFromPath($bannerTop, 'banner-top');
        }
        if (file_exists($bannerBottom)) {
            $message->embedFromPath($bannerBottom, 'banner-bottom');
        }
        if ($stageImage) {
            $stagePath = public_path('images/email/' . $stageImage);
            if (file_exists($stagePath)) {
                $message->embedFromPath($stagePath, 'tracking-stage');
            }
        }
    }

    private function fallbackMail(object $notifiable): MailMessage
    {
        $carrierInfo = $this->getCarrierInfo();

        $items = $this->combinedItems();

        return (new MailMessage)
            ->subject('Actualizaci\u00f3n de tu pedido #' . $this->order->order_number . ' - Lyrium')
            ->withSymfonyMessage(fn ($message) => $this->embedTrackingImages($message, null))
            ->view('emails.notifications.order-tracking', [
                'customerName'    => $notifiable->name,
                'orderNumber'     => $this->order->order_number,
                'trackingTitle'   => $this->isServiceOnly() ? 'Tu servicio ha sido actualizado' : 'Tu pedido ha sido actualizado',
                'bannerTopCid'    => 'banner-top',
                'bannerBottomCid' => 'banner-bottom',
                'imageCid'        => null,
                'items'           => $items,
                'subtotal'        => number_format((float) $this->order->subtotal, 2),
                'shippingCost'    => number_format((float) $this->order->shipping_cost, 2),
                'total'           => number_format((float) $this->order->total, 2),
                'actionUrl'       => config('app.frontend_url') . '/customer/orders',
                'showTagline'     => true,
                'hideHeader'      => true,
                'hideFooter'      => true,
                'carrierName'     => $carrierInfo['name'],
                'trackingCode'    => $carrierInfo['tracking_code'],
                'trackingUrl'     => $carrierInfo['tracking_url'],
                'carrierFields'   => $carrierInfo['fields'],
            ]);
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
        $label = $this->isServiceOnly()
            ? (self::SERVICE_STATUS_LABELS[$this->order->status] ?? 'Actualizado')
            : (self::STATUS_LABELS[$this->order->status] ?? 'Actualizado');

        return [
            'type'         => 'order_tracking',
            'order_id'     => $this->order->id,
            'order_number' => $this->order->order_number,
            'status'       => $this->order->status,
            'subject'      => "Pedido #{$this->order->order_number}: {$label}",
        ];
    }
}
