<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\Order;
use App\Models\ServiceBooking;
use App\Services\ReceiptValidationService;
use Illuminate\Console\Command;

/**
 * Cierre automático por inacción: pedidos entregados y reservas completadas
 * que el cliente no validó dentro de la ventana configurada se marcan como
 * validados con source=auto_expired (sin bono de Lirios).
 */
final class ExpireUnvalidatedReceipts extends Command
{
    protected $signature = 'receipts:expire-unvalidated';

    protected $description = 'Cierra automáticamente pedidos/reservas entregados sin validación del cliente tras N días';

    public function handle(ReceiptValidationService $receiptValidationService): int
    {
        $days = ReceiptValidationService::autoExpireDays();
        $cutoff = now()->subDays($days);

        $expiredOrders = 0;
        Order::query()
            ->where('status', Order::STATUS_DELIVERED)
            ->whereNull('customer_validated_at')
            ->whereNotNull('delivered_at')
            ->where('delivered_at', '<=', $cutoff)
            ->orderBy('id')
            ->chunkById(100, function ($orders) use ($receiptValidationService, &$expiredOrders) {
                foreach ($orders as $order) {
                    $result = $receiptValidationService->validateOrder(
                        $order,
                        ReceiptValidationService::SOURCE_AUTO_EXPIRED,
                    );
                    if ($result['validated']) {
                        $expiredOrders++;
                    }
                }
            });

        $expiredBookings = 0;
        ServiceBooking::query()
            ->where('status', ServiceBooking::STATUS_COMPLETED)
            ->whereNull('customer_validated_at')
            ->whereNotNull('completed_at')
            ->where('completed_at', '<=', $cutoff)
            ->orderBy('id')
            ->chunkById(100, function ($bookings) use ($receiptValidationService, &$expiredBookings) {
                foreach ($bookings as $booking) {
                    $result = $receiptValidationService->validateBooking(
                        $booking,
                        ReceiptValidationService::SOURCE_AUTO_EXPIRED,
                    );
                    if ($result['validated']) {
                        $expiredBookings++;
                    }
                }
            });

        $this->info("Cerrados por inacción (> {$days} días): {$expiredOrders} pedido(s), {$expiredBookings} reserva(s).");

        return self::SUCCESS;
    }
}
