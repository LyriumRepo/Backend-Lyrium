<?php

declare(strict_types=1);

namespace App\Listeners;

use App\Events\OrderPaymentConfirmed;
use App\Services\OrderPaymentService;
use Illuminate\Support\Facades\Log;

final class GenerateInvoicesForOrder
{
    public function __construct(
        private readonly OrderPaymentService $orderPaymentService,
    ) {}

    public function handle(OrderPaymentConfirmed $event): void
    {
        Log::info('GenerateInvoicesForOrder: procesando pago confirmado', [
            'order_id' => $event->order->id,
            'order_number' => $event->order->order_number,
        ]);

        $this->orderPaymentService->processSuccessfulPayment(
            order: $event->order,
            paymentMethod: $event->paymentMethod,
        );
    }
}
