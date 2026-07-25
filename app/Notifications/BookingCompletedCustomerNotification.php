<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Channels\PushChannel;
use App\Models\ServiceBooking;
use App\Services\LiriosService;
use App\Services\ReceiptValidationService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\URL;

/**
 * Invita al cliente a validar la finalización de su reserva de servicio.
 * Incluye link firmado de un clic (sin login) que expira junto con
 * la ventana de auto-cierre. Reutiliza la misma plantilla visual que
 * OrderStatusTrackingNotification (banners, tabla de detalle, redes
 * sociales) para que todos los correos de seguimiento luzcan igual.
 */
final class BookingCompletedCustomerNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        private readonly ServiceBooking $booking,
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
            'receipt.validate.booking',
            now()->addDays($days),
            ['booking' => $this->booking->id],
        );

        $serviceName = $this->booking->service?->name ?? 'tu servicio';
        $total = (float) $this->booking->total_price;

        return (new MailMessage)
            ->subject('✅ ¡Tu servicio fue completado! Valídalo y gana Lirios')
            ->withSymfonyMessage(fn ($message) => $this->embedBanners($message))
            ->view('emails.notifications.order-tracking', [
                'customerName' => $notifiable->name,
                'orderNumber' => (string) $this->booking->id,
                'trackingTitle' => '¡GRACIAS POR TU COMPRA!',
                'subtitle' => sprintf(
                    '¡Hola, %s! Tu reserva de %s fue completada. Confírmanos si todo salió bien y gana %d Lirios para tu próxima compra.',
                    $notifiable->name,
                    $serviceName,
                    LiriosService::VALIDATION_BONUS_LIRIOS,
                ),
                'bannerTopCid' => 'banner-top',
                'bannerBottomCid' => 'banner-bottom',
                'imageCid' => null,
                'items' => [[
                    'name' => $serviceName,
                    'quantity' => 1,
                    'line_total' => number_format($total, 2),
                ]],
                'subtotal' => number_format($total, 2),
                'shippingCost' => number_format(0, 2),
                'total' => number_format($total, 2),
                'actionUrl' => $url,
                'actionLabel' => 'Confirmar y Validar Servicio',
                'extraNote' => "Si no haces nada, la reserva se cerrará automáticamente en {$days} días (sin bono).",
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
            'title' => '✅ ¡Tu servicio fue completado!',
            'body' => sprintf(
                'Valida tu reserva y gana %d Lirios.',
                LiriosService::VALIDATION_BONUS_LIRIOS,
            ),
            'data' => [
                'type' => 'booking_completed_validate',
                'booking_id' => (string) $this->booking->id,
                'url' => '/customer/bookings',
            ],
        ];
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'booking_completed_validate',
            'title' => '✅ ¡Tu servicio fue completado!',
            'message' => sprintf(
                'Valida tu reserva #%d y gana %d Lirios para tu próxima compra.',
                $this->booking->id,
                LiriosService::VALIDATION_BONUS_LIRIOS,
            ),
            'booking_id' => $this->booking->id,
            'lirios_bonus' => LiriosService::VALIDATION_BONUS_LIRIOS,
            'action_url' => '/customer/bookings',
        ];
    }
}
