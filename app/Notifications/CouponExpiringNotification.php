<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Channels\PushChannel;
use App\Models\Coupon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

final class CouponExpiringNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        private readonly Coupon $coupon,
        private readonly int $daysLeft,
    ) {}

    public function via(object $notifiable): array
    {
        $channels = ['database'];

        $settings = $notifiable->notificationSetting;

        if ($settings?->wantsPush() ?? true) {
            $channels[] = PushChannel::class;
        }

        if ($settings?->wantsEmailPromotions() ?? true) {
            $channels[] = 'mail';
        }

        return $channels;
    }

    public function toPush(object $notifiable): array
    {
        return [
            'title' => '🎟️ Cupón por vencer',
            'body'  => "El cupón \"{$this->coupon->code}\" vence en {$this->daysLeft} día(s). ¡Úsalo antes que expire!",
            'data'  => [
                'type'      => 'coupon_expiring',
                'coupon_id' => (string) $this->coupon->id,
                'url'       => '/seller/coupons',
            ],
        ];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $discount = $this->coupon->type === Coupon::TYPE_PERCENTAGE
            ? "{$this->coupon->value}% de descuento"
            : "S/ {$this->coupon->value} de descuento";

        return (new MailMessage)
            ->subject("🎟️ Cupón \"{$this->coupon->code}\" vence en {$this->daysLeft} día(s) — Lyrium")
            ->greeting('¡Hola, ' . $notifiable->name . '!')
            ->line("Tu cupón **{$this->coupon->code}** vencerá en **{$this->daysLeft} día(s)**.")
            ->line("**Descuento:** {$discount}")
            ->when(
                $this->coupon->expires_at,
                fn ($m) => $m->line("**Fecha de vencimiento:** {$this->coupon->expires_at->format('d/m/Y H:i')}")
            )
            ->line('Considera extender la vigencia si deseas seguir ofreciendo este cupón.')
            ->action('Gestionar cupones', config('app.frontend_url') . '/seller/coupons');
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type'       => 'coupon_expiring',
            'title'      => "🎟️ Cupón \"{$this->coupon->code}\" vence en {$this->daysLeft} día(s)",
            'message'    => "El cupón \"{$this->coupon->code}\" expira el {$this->coupon->expires_at?->format('d/m/Y')}. ¡Renuévalo si deseas seguir ofreciéndolo!",
            'coupon_id'  => $this->coupon->id,
            'coupon_code' => $this->coupon->code,
            'coupon_name' => $this->coupon->name,
            'days_left'  => $this->daysLeft,
            'expires_at' => $this->coupon->expires_at?->toIso8601String(),
            'action_url' => '/seller/coupons',
        ];
    }
}
