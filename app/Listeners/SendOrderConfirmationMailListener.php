<?php

declare(strict_types=1);

namespace App\Listeners;

use App\Events\OrderPaymentConfirmed;
use App\Mail\OrderConfirmationMail;
use App\Notifications\OrderPaymentConfirmedNotification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

final class SendOrderConfirmationMailListener implements ShouldQueue
{
    public function handle(OrderPaymentConfirmed $event): void
    {
        $order = $event->order;

        if (!$order->items()->exists()) {
            return;
        }

        $user = $order->user;
        if (!$user || !$user->email) {
            return;
        }

        $settings = $user->notificationSetting;
        if ($settings?->wantsEmailOrder() ?? true) {
            try {
                Mail::to($user->email)->send(new OrderConfirmationMail($order));
            } catch (\Throwable $mailEx) {
                Log::warning('Error enviando correo de confirmación de orden', [
                    'order_id' => $order->id,
                    'error' => $mailEx->getMessage(),
                    'trace' => $mailEx->getTraceAsString(),
                ]);
            }
        }

        try {
            $user->notify(new OrderPaymentConfirmedNotification($order));
        } catch (\Throwable $e) {
            Log::error('SendOrderConfirmationMailListener: error al notificar', [
                'order_id' => $order->id,
                'user_id' => $user->id,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
