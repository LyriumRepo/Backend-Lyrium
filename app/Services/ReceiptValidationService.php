<?php

declare(strict_types=1);

namespace App\Services;

use App\Events\BookingReceiptValidated;
use App\Events\OrderReceiptValidated;
use App\Models\Order;
use App\Models\ServiceBooking;
use Illuminate\Support\Facades\DB;

/**
 * Validación de recepción por parte del cliente.
 *
 * Capa aditiva sobre el state machine existente: NO modifica Order.status
 * ni ServiceBooking.status — solo escribe customer_validated_at + validation_source.
 * Idempotente: la guardia atómica (whereNull) garantiza que una orden/reserva
 * solo se valida una vez, sin importar cuántas veces se llame (doble clic,
 * replay del link de email, carrera con el auto-cierre).
 */
final class ReceiptValidationService
{
    public const SOURCE_MANUAL = 'manual';

    public const SOURCE_EMAIL = 'email';

    public const SOURCE_AUTO_EXPIRED = 'auto_expired';

    public const SOURCES = [
        self::SOURCE_MANUAL,
        self::SOURCE_EMAIL,
        self::SOURCE_AUTO_EXPIRED,
    ];

    /** Clave de SystemConfig con los días de espera antes del auto-cierre. */
    public const AUTO_EXPIRE_DAYS_KEY = 'receipt_validation_auto_expire_days';

    /** Días por defecto si la clave no existe en SystemConfig. */
    public const AUTO_EXPIRE_DAYS_DEFAULT = 7;

    /**
     * @return array{validated: bool, already_validated: bool, validation_source: ?string}
     */
    public function validateOrder(Order $order, string $source): array
    {
        $this->assertValidSource($source);

        return DB::transaction(function () use ($order, $source) {
            $affected = Order::query()
                ->whereKey($order->id)
                ->where('status', Order::STATUS_DELIVERED)
                ->whereNull('customer_validated_at')
                ->update([
                    'customer_validated_at' => now(),
                    'validation_source' => $source,
                ]);

            $order->refresh();

            if ($affected === 0) {
                return [
                    'validated' => false,
                    'already_validated' => $order->customer_validated_at !== null,
                    'validation_source' => $order->validation_source,
                ];
            }

            if ($source !== self::SOURCE_AUTO_EXPIRED) {
                OrderReceiptValidated::dispatch($order, $source);
            }

            return [
                'validated' => true,
                'already_validated' => false,
                'validation_source' => $source,
            ];
        });
    }

    /**
     * @return array{validated: bool, already_validated: bool, validation_source: ?string}
     */
    public function validateBooking(ServiceBooking $booking, string $source): array
    {
        $this->assertValidSource($source);

        return DB::transaction(function () use ($booking, $source) {
            $affected = ServiceBooking::query()
                ->whereKey($booking->id)
                ->where('status', ServiceBooking::STATUS_COMPLETED)
                ->whereNull('customer_validated_at')
                ->update([
                    'customer_validated_at' => now(),
                    'validation_source' => $source,
                ]);

            $booking->refresh();

            if ($affected === 0) {
                return [
                    'validated' => false,
                    'already_validated' => $booking->customer_validated_at !== null,
                    'validation_source' => $booking->validation_source,
                ];
            }

            if ($source !== self::SOURCE_AUTO_EXPIRED) {
                BookingReceiptValidated::dispatch($booking, $source);
            }

            return [
                'validated' => true,
                'already_validated' => false,
                'validation_source' => $source,
            ];
        });
    }

    public static function autoExpireDays(): int
    {
        return (int) \App\Models\SystemConfig::getByKey(
            self::AUTO_EXPIRE_DAYS_KEY,
            self::AUTO_EXPIRE_DAYS_DEFAULT,
        );
    }

    private function assertValidSource(string $source): void
    {
        if (! in_array($source, self::SOURCES, true)) {
            throw new \InvalidArgumentException("Fuente de validación inválida: {$source}");
        }
    }
}
