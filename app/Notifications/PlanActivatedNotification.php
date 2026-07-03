<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Channels\PushChannel;
use App\Models\Plan;
use App\Models\PlanRequest;
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
        private readonly ?PlanRequest $planRequest = null,
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
        $planName    = $this->plan->name;
        $storeName   = $this->subscription->store?->trade_name ?? 'tu tienda';
        $startsAt    = $this->subscription->starts_at?->format('d/m/Y') ?? now()->format('d/m/Y');
        $endsAt      = $this->subscription->ends_at?->format('d/m/Y') ?? '—';
        $months      = $this->planRequest?->months ?? 1;
        $totalAmount = $this->planRequest?->total_amount;
        $paymentMethod = $this->planRequest?->payment_method ?? 'izipay';
        $referenceId = $this->planRequest?->izipay_order_id ?? '—';
        $isTrial     = $this->planRequest?->isTrial() ?? false;
        $commission  = number_format((float) $this->plan->commission_rate * 100, 0).'%';

        return (new MailMessage)
            ->subject("Confirmación de suscripción — Plan {$planName} · Lyrium")
            ->view('emails.notifications.plan-activated', [
                'name'          => $notifiable->name,
                'planName'      => $planName,
                'storeName'     => $storeName,
                'startsAt'      => $startsAt,
                'endsAt'        => $endsAt,
                'months'        => $months,
                'totalAmount'   => $totalAmount,
                'paymentMethod' => $paymentMethod,
                'referenceId'   => $referenceId,
                'isTrial'       => $isTrial,
                'commission'    => $commission,
                'actionUrl'     => config('app.frontend_url').'/seller/planes',
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
