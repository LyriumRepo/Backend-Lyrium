<?php

declare(strict_types=1);

namespace App\Listeners;

use App\Events\OrderPaymentConfirmed;
use App\Services\LiriosService;
use Illuminate\Support\Facades\Log;

final class AccrueLiriosForOrder
{
    public function __construct(
        private readonly LiriosService $liriosService,
    ) {}

    public function handle(OrderPaymentConfirmed $event): void
    {
        $order = $event->order;

        // No acumular si la orden no tiene usuario (invitado) o si el total es 0
        if (! $order->user_id || $order->total <= 0) {
            return;
        }

        try {
            $this->liriosService->accrue($order->user_id, (float) $order->total, $order);
            Log::info('[Lirios] Puntos acumulados via OrderPaymentConfirmed', [
                'order_id' => $order->id,
                'user_id'  => $order->user_id,
                'total'    => $order->total,
            ]);
        } catch (\Throwable $e) {
            // No bloquear el flujo de pago si falla la acumulación
            Log::error('[Lirios] Error acumulando puntos', [
                'order_id' => $order->id,
                'error'    => $e->getMessage(),
            ]);
        }
    }
}
