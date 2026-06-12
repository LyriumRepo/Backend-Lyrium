<?php

declare(strict_types=1);

namespace App\Listeners;

use App\Events\OrderPaymentConfirmed;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Support\Facades\Log;

final class GenerateInvoicesForOrder
{
    public function __construct(
        private readonly Application $app,
    ) {}

    public function handle(OrderPaymentConfirmed $event): void
    {
        Log::info('GenerateInvoicesForOrder: procesando pago confirmado', [
            'order_id' => $event->order->id,
            'order_number' => $event->order->order_number,
        ]);

        try {
            $service = $this->app->make(\App\Services\OrderPaymentService::class);
            $service->processSuccessfulPayment(
                order: $event->order,
                paymentMethod: $event->paymentMethod,
            );
        } catch (\Throwable $e) {
            Log::error('GenerateInvoicesForOrder: error procesando pago confirmado', [
                'order_id' => $event->order->id,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
