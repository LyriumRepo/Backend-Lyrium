<?php

declare(strict_types=1);

namespace App\Listeners;

use App\Events\BookingReceiptValidated;
use App\Events\OrderReceiptValidated;
use App\Services\LiriosService;
use Illuminate\Support\Facades\Log;

/**
 * Acredita el bono de Lirios cuando el cliente valida la recepción
 * de un pedido o servicio (fuentes manual/email; auto_expired nunca
 * dispara los eventos, por lo que no llega aquí).
 *
 * Mismo patrón que AccrueLiriosForOrder: try/catch para que un fallo
 * en Lirios jamás bloquee el flujo de validación.
 */
final class AccrueLiriosForReceiptValidation
{
    public function __construct(
        private readonly LiriosService $liriosService,
    ) {}

    public function handleOrder(OrderReceiptValidated $event): void
    {
        $order = $event->order;

        if (! $order->user_id) {
            return;
        }

        try {
            $this->liriosService->creditValidationBonus(
                $order->user_id,
                'order',
                $order->id,
                "Bono por validar recepción de la orden #{$order->order_number}",
            );
            Log::info('[Lirios] Bono de validación acreditado (orden)', [
                'order_id' => $order->id,
                'user_id' => $order->user_id,
                'source' => $event->source,
            ]);
        } catch (\Throwable $e) {
            Log::error('[Lirios] Error acreditando bono de validación (orden)', [
                'order_id' => $order->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    public function handleBooking(BookingReceiptValidated $event): void
    {
        $booking = $event->booking;

        if (! $booking->user_id) {
            return;
        }

        try {
            $this->liriosService->creditValidationBonus(
                $booking->user_id,
                'service_booking',
                $booking->id,
                "Bono por validar finalización de la reserva #{$booking->id}",
            );
            Log::info('[Lirios] Bono de validación acreditado (reserva)', [
                'booking_id' => $booking->id,
                'user_id' => $booking->user_id,
                'source' => $event->source,
            ]);
        } catch (\Throwable $e) {
            Log::error('[Lirios] Error acreditando bono de validación (reserva)', [
                'booking_id' => $booking->id,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
