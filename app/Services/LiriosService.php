<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\LiriosAccount;
use App\Models\LiriosTransaction;
use App\Models\Order;
use App\Models\Store;
use Illuminate\Support\Facades\DB;

final class LiriosService
{
    /** Lirios otorgados por cada sol (redondeado) pagado en una compra. */
    private const LIRIOS_PER_SOL_EARNED = 10;

    /** Valor en soles de 1 Lirio al canjearlo (1000 Lirios = S/ 1.00). */
    private const SOLES_PER_LIRIO_REDEEMED = 0.001;

    /** Tope absoluto del porcentaje de descuento por Lirios, sin importar la config. de la tienda. */
    private const MAX_LIRIOS_PERCENT = 3.00;

    public function getOrCreateAccount(int $userId): LiriosAccount
    {
        return LiriosAccount::firstOrCreate(
            ['user_id' => $userId],
            ['balance' => 0],
        );
    }

    public function getBalance(int $userId): int
    {
        $account = $this->getOrCreateAccount($userId);

        return $account->balance;
    }

    /**
     * Calcula la elegibilidad para usar Lirios en el checkout.
     *
     * @param  int  $cartTotal  Total del carrito en soles (subtotal + shipping - descuento cupón)
     * @param  array  $storeIds  IDs de tiendas involucradas (para usar el lirios_percent más restrictivo)
     */
    public function checkoutEligibility(int $userId, float $cartTotal, array $storeIds = []): array
    {
        $balance = $this->getBalance($userId);
        $valorVenta = $cartTotal / 1.18;
        $liriosPercent = $this->getEffectivePercent($storeIds);
        $maxDiscount = $valorVenta * ($liriosPercent / 100);
        $eligible = $maxDiscount >= 2.00;

        return [
            'balance' => $balance,
            'max_discount' => round($maxDiscount, 2),
            'eligible' => $eligible,
            'lirios_percent' => $liriosPercent,
            'valor_venta' => round($valorVenta, 2),
            'max_lirios_usables' => $eligible
                ? (int) floor(min($balance, $maxDiscount / self::SOLES_PER_LIRIO_REDEEMED))
                : 0,
        ];
    }

    /**
     * Redime Lirios (descuenta del balance) durante la creación de la orden.
     */
    public function redeem(int $userId, int $amount, Order $order): LiriosTransaction
    {
        $account = $this->getOrCreateAccount($userId);

        if ($account->balance < $amount) {
            throw new \RuntimeException('No tienes suficientes Lyriopuntos.');
        }

        $balanceBefore = $account->balance;

        return DB::transaction(function () use ($account, $userId, $amount, $order, $balanceBefore) {
            $account->decrement('balance', $amount);

            return LiriosTransaction::create([
                'user_id' => $userId,
                'type' => 'redeem',
                'amount' => $amount,
                'balance_before' => $balanceBefore,
                'balance_after' => $balanceBefore - $amount,
                'reference_type' => 'order',
                'reference_id' => $order->id,
                'description' => "Canje en orden #{$order->order_number}",
            ]);
        });
    }

    /**
     * Acumula Lirios después de un pago exitoso.
     */
    public function accrue(int $userId, float $totalPaid, Order $order): LiriosTransaction
    {
        $account = $this->getOrCreateAccount($userId);
        $roundedAmount = round($totalPaid);
        $points = (int) ($roundedAmount * self::LIRIOS_PER_SOL_EARNED);
        $balanceBefore = $account->balance;

        if ($points <= 0) {
            throw new \RuntimeException('El monto pagado no genera Lyriopuntos.');
        }

        return DB::transaction(function () use ($account, $userId, $points, $order, $balanceBefore) {
            $account->increment('balance', $points);

            return LiriosTransaction::create([
                'user_id' => $userId,
                'type' => 'accrue',
                'amount' => $points,
                'balance_before' => $balanceBefore,
                'balance_after' => $balanceBefore + $points,
                'reference_type' => 'order',
                'reference_id' => $order->id,
                'description' => "Compra en orden #{$order->order_number}",
            ]);
        });
    }

    /**
     * Obtiene el porcentaje de Lirios efectivo (el más restrictivo entre las tiendas).
     */
    private function getEffectivePercent(array $storeIds): float
    {
        if (empty($storeIds)) {
            return self::MAX_LIRIOS_PERCENT;
        }

        $minPercent = Store::whereIn('id', $storeIds)
            ->min('lirios_percent');

        return min((float) ($minPercent ?? self::MAX_LIRIOS_PERCENT), self::MAX_LIRIOS_PERCENT);
    }

    /**
     * Calcula el descuento máximo aplicable y valida si un monto de Lirios es válido.
     */
    public function validateAndCalculate(int $userId, int $liriosToUse, float $cartTotal, array $storeIds = []): array
    {
        $eligibility = $this->checkoutEligibility($userId, $cartTotal, $storeIds);

        if (! $eligibility['eligible']) {
            throw new \RuntimeException('El descuento mínimo no alcanza los S/ 2.00.');
        }

        if ($liriosToUse <= 0) {
            throw new \RuntimeException('La cantidad de Lyriopuntos debe ser mayor a 0.');
        }

        $maxLirios = $eligibility['max_lirios_usables'];
        if ($liriosToUse > $maxLirios) {
            throw new \RuntimeException("Solo puedes usar hasta {$maxLirios} Lyriopuntos en esta orden.");
        }

        if ($liriosToUse > $eligibility['balance']) {
            throw new \RuntimeException('No tienes suficientes Lyriopuntos.');
        }

        return [
            'lirios_used' => $liriosToUse,
            'lirios_discount' => round($liriosToUse * self::SOLES_PER_LIRIO_REDEEMED, 2),
        ];
    }
}
