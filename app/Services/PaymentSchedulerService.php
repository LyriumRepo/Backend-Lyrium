<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\PaymentSchedule;
use App\Models\SellerPayment;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

final class PaymentSchedulerService
{
    private const DEFAULT_PER_PAGE = 15;

    private const MAX_PER_PAGE = 100;

    public function schedulePayment(
        int $storeId,
        float $amount,
        ?int $orderId = null,
        float $commissionRate = 0
    ): SellerPayment {
        $commissionAmount = $amount * ($commissionRate / 100);
        $netAmount = $amount - $commissionAmount;

        $scheduledFor = $this->getNextPaymentDate();

        return SellerPayment::create([
            'store_id' => $storeId,
            'order_id' => $orderId,
            'payment_number' => SellerPayment::generatePaymentNumber(),
            'status' => SellerPayment::STATUS_PENDING,
            'amount' => $amount,
            'commission_rate' => $commissionRate,
            'commission_amount' => $commissionAmount,
            'net_amount' => $netAmount,
            'scheduled_for' => $scheduledFor,
        ]);
    }

    public function scheduleBulkPayments(array $payments): Collection
    {
        $scheduled = collect();

        foreach ($payments as $payment) {
            $scheduled->push($this->schedulePayment(
                storeId: $payment['store_id'],
                amount: $payment['amount'],
                orderId: $payment['order_id'] ?? null,
                commissionRate: $payment['commission_rate'] ?? 0
            ));
        }

        return $scheduled;
    }

    public function getNextPaymentDate(): \Carbon\Carbon
    {
        $schedule = PaymentSchedule::query()
            ->where('is_active', true)
            ->orderByRaw("CASE day 
                WHEN 'monday' THEN 1 
                WHEN 'tuesday' THEN 2 
                WHEN 'wednesday' THEN 3 
                WHEN 'thursday' THEN 4 
                WHEN 'friday' THEN 5 
                WHEN 'saturday' THEN 6 
                WHEN 'sunday' THEN 7 
            END")
            ->first();

        if (! $schedule) {
            return now()->addDay();
        }

        return $schedule->getNextPaymentDate();
    }

    public function getPendingPayments(): Collection
    {
        return SellerPayment::query()
            ->where('status', SellerPayment::STATUS_PENDING)
            ->where('scheduled_for', '<=', now())
            ->with(['store', 'order'])
            ->get();
    }

    public function processPayment(int $paymentId, string $method, ?string $reference = null): SellerPayment
    {
        $payment = SellerPayment::query()
            ->with(['store'])
            ->findOrFail($paymentId);

        if (! $payment->canProcess()) {
            throw new \InvalidArgumentException('Este pago no puede ser procesado aún');
        }

        $payment->markProcessing();

        $payment->update([
            'payment_method' => $method,
            'reference' => $reference,
            'status' => SellerPayment::STATUS_COMPLETED,
            'processed_at' => now(),
        ]);

        return $payment->fresh();
    }

    public function getStorePayments(int $storeId, int $perPage = self::DEFAULT_PER_PAGE): LengthAwarePaginator
    {
        $perPage = min($perPage, self::MAX_PER_PAGE);

        return SellerPayment::query()
            ->where('store_id', $storeId)
            ->with(['order'])
            ->latest()
            ->paginate($perPage);
    }

    public function getStorePendingPayments(int $storeId): Collection
    {
        return SellerPayment::query()
            ->where('store_id', $storeId)
            ->where('status', SellerPayment::STATUS_PENDING)
            ->with(['order'])
            ->get();
    }

    public function getStoreCompletedPayments(int $storeId): Collection
    {
        return SellerPayment::query()
            ->where('store_id', $storeId)
            ->where('status', SellerPayment::STATUS_COMPLETED)
            ->with(['order'])
            ->orderBy('processed_at', 'desc')
            ->get();
    }

    public function getAllPayments(
        ?string $status = null,
        ?int $storeId = null,
        int $perPage = self::DEFAULT_PER_PAGE
    ): LengthAwarePaginator {
        $perPage = min($perPage, self::MAX_PER_PAGE);

        $query = SellerPayment::query()
            ->with(['store', 'order']);

        if ($status) {
            $query->where('status', $status);
        }

        if ($storeId) {
            $query->where('store_id', $storeId);
        }

        return $query->latest()->paginate($perPage);
    }

    public function getTotalPendingAmount(): float
    {
        return SellerPayment::query()
            ->where('status', SellerPayment::STATUS_PENDING)
            ->sum('net_amount');
    }

    public function getTotalPendingAmountByStore(int $storeId): float
    {
        return SellerPayment::query()
            ->where('store_id', $storeId)
            ->where('status', SellerPayment::STATUS_PENDING)
            ->sum('net_amount');
    }

    public function cancelPayment(int $paymentId): SellerPayment
    {
        $payment = SellerPayment::query()->findOrFail($paymentId);

        if (! $payment->isPending()) {
            throw new \InvalidArgumentException('Solo se pueden cancelar pagos pendientes');
        }

        $payment->update([
            'status' => SellerPayment::STATUS_CANCELLED,
        ]);

        return $payment->fresh();
    }

    public function reschedulePayment(int $paymentId, \Carbon\Carbon $newDate): SellerPayment
    {
        $payment = SellerPayment::query()->findOrFail($paymentId);

        if (! $payment->isPending()) {
            throw new \InvalidArgumentException('Solo se pueden reprogramar pagos pendientes');
        }

        $payment->update([
            'scheduled_for' => $newDate,
        ]);

        return $payment->fresh();
    }
}
