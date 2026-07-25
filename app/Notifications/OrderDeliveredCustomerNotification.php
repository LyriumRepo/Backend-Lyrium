<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Channels\PushChannel;
use App\Models\Order;
use App\Services\LiriosService;
use App\Services\ReceiptValidationService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\URL;

/**
 * Invita al cliente a validar la recepción de su pedido entregado.
 * Incluye link firmado de un clic (sin login) que expira junto con
 * la ventana de auto-cierre. Reutiliza la misma plantilla visual que
 * OrderStatusTrackingNotification (banners, tabla de detalle, redes
 * sociales) para que todos los correos de seguimiento luzcan igual.
 */
final class OrderDeliveredCustomerNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        private readonly Order $order,
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

    /**
     * Embebe los banners fijos (top/bottom) como adjuntos inline con Content-ID,
     * igual que OrderStatusTrackingNotification, para reutilizar exactamente el
     * mismo aspecto visual en este correo.
     */
    private function embedBanners(\Symfony\Component\Mime\Email $message): void
    {
        $bannerTop = public_path('images/email/banner-top.jpg');
        $bannerBottom = public_path('images/email/banner-bottom.jpg');

        if (file_exists($bannerTop)) {
            $message->embedFromPath($bannerTop, 'banner-top');
        }
        if (file_exists($bannerBottom)) {
            $message->embedFromPath($bannerBottom, 'banner-bottom');
        }
    }

    public function toMail(object $notifiable): MailMessage
    {
        $days = ReceiptValidationService::autoExpireDays();
        $url = URL::temporarySignedRoute(
            'receipt.validate.order',
            now()->addDays($days),
            ['order' => $this->order->id],
        );

        return (new MailMessage)
            ->subject("📦 ¡Tu pedido #{$this->order->order_number} fue entregado! Valídalo y gana Lirios")
            ->withSymfonyMessage(fn ($message) => $this->embedBanners($message))
            ->view('emails.notifications.order-tracking', [
                'customerName' => $notifiable->name,
                'orderNumber' => $this->order->order_number,
                'trackingTitle' => '¡GRACIAS POR TU COMPRA!',
                'subtitle' => sprintf(
                    '¡Hola, %s! Tu pedido #%s fue entregado. Confírmanos si te llegó bien y gana %d Lirios para tu próxima compra.',
                    $notifiable->name,
                    $this->order->order_number,
                    LiriosService::VALIDATION_BONUS_LIRIOS,
                ),
                'bannerTopCid' => 'banner-top',
                'bannerBottomCid' => 'banner-bottom',
                'imageCid' => null,
                'items' => $this->combinedItems(),
                'subtotal' => number_format((float) $this->order->subtotal, 2),
                'shippingCost' => number_format((float) $this->order->shipping_cost, 2),
                'total' => number_format((float) $this->order->total, 2),
                'actionUrl' => $url,
                'actionLabel' => 'Confirmar y Validar Recepción',
                'extraNote' => "Si no haces nada, el pedido se cerrará automáticamente en {$days} días (sin bono).",
                'showTagline' => true,
                'hideHeader' => true,
                'hideFooter' => true,
                'carrierName' => null,
                'trackingCode' => null,
                'trackingUrl' => null,
                'carrierFields' => [],
            ]);
    }

    public function toPush(object $notifiable): array
    {
        return [
            'title' => '📦 ¡Tu pedido fue entregado!',
            'body' => sprintf(
                'Valida la recepción del pedido #%s y gana %d Lirios.',
                $this->order->order_number,
                LiriosService::VALIDATION_BONUS_LIRIOS,
            ),
            'data' => [
                'type' => 'order_delivered_validate',
                'order_id' => (string) $this->order->id,
                'url' => '/customer/orders',
            ],
        ];
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'order_delivered_validate',
            'title' => '📦 ¡Tu pedido fue entregado!',
            'message' => sprintf(
                'Valida la recepción del pedido #%s y gana %d Lirios para tu próxima compra.',
                $this->order->order_number,
                LiriosService::VALIDATION_BONUS_LIRIOS,
            ),
            'order_id' => $this->order->id,
            'order_number' => $this->order->order_number,
            'lirios_bonus' => LiriosService::VALIDATION_BONUS_LIRIOS,
            'action_url' => '/customer/orders',
        ];
    }
}
