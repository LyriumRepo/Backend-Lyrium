<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Channels\PushChannel;
use App\Models\Subscription;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

final class PlanExpiringNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        private readonly Subscription $subscription,
        private readonly int $daysLeft,
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
        $planName  = $this->subscription->plan?->name ?? 'tu plan';
        $storeName = $this->subscription->store?->trade_name ?? 'tu tienda';
        $endsAt    = $this->subscription->ends_at?->format('d/m/Y') ?? '—';

        return (new MailMessage)
            ->subject("⏰ Tu plan vence en {$this->daysLeft} día(s) — Lyrium BioMarketplace")
            ->view('emails.notifications.plan-expiring', [
                'name'      => $notifiable->name,
                'storeName' => $storeName,
                'planName'  => $planName,
                'daysLeft'  => $this->daysLeft,
                'endsAt'    => $endsAt,
                'actionUrl' => config('app.frontend_url') . '/seller/planes',
            ]);
    }

    public function toPush(object $notifiable): array
    {
        $planName = $this->subscription->plan?->name ?? 'tu plan';
        return [
            'title' => '⏰ Plan próximo a vencer',
            'body'  => "{$planName} vence en {$this->daysLeft} día(s). Renueva para seguir operando.",
            'data'  => [
                'type' => 'plan_expiring',
                'url'  => '/seller/planes',
            ],
        ];
    }

    public function toArray(object $notifiable): array
    {
        $planName = $this->subscription->plan?->name ?? 'Plan';
        return [
            'type'      => 'plan_expiring',
            'plan_name' => $planName,
            'days_left' => $this->daysLeft,
            'ends_at'   => $this->subscription->ends_at?->toDateString(),
            'subject'   => "{$planName} vence en {$this->daysLeft} día(s) — renueva para seguir operando",
        ];
    }
}
