<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Channels\PushChannel;
use App\Models\Plan;
use App\Models\Subscription;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

final class PlanActivatedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        private readonly Plan $plan,
        private readonly Subscription $subscription,
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
        $planName  = $this->plan->name;
        $storeName = $this->subscription->store?->trade_name ?? 'tu tienda';
        $endsAt    = $this->subscription->ends_at?->format('d/m/Y') ?? '—';

        return (new MailMessage)
            ->subject("Plan {$planName} activado — Lyrium BioMarketplace")
            ->view('emails.notifications.plan-activated', [
                'name'      => $notifiable->name,
                'planName'  => $planName,
                'storeName' => $storeName,
                'endsAt'    => $endsAt,
                'actionUrl' => config('app.frontend_url').'/seller/planes',
            ]);
    }

    public function toPush(object $notifiable): array
    {
        $planName = $this->plan->name;
        return [
            'title' => 'Plan activado',
            'body'  => "Tu plan {$planName} ha sido activado. Ya puedes vender en Lyrium.",
            'data'  => [
                'type' => 'plan_activated',
                'url'  => '/seller/planes',
            ],
        ];
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type'      => 'plan_activated',
            'plan_name' => $this->plan->name,
            'plan_slug' => $this->plan->slug,
            'ends_at'   => $this->subscription->ends_at?->toDateString(),
            'subject'   => "Tu plan {$this->plan->name} ha sido activado",
        ];
    }
}
