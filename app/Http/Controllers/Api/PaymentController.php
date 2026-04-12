<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\UpdatePaymentScheduleRequest;
use App\Http\Resources\PaymentScheduleResource;
use App\Http\Resources\SellerPaymentResource;
use App\Models\PaymentSchedule;
use App\Services\PaymentSchedulerService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

final class PaymentController extends Controller
{
    public function __construct(
        private readonly PaymentSchedulerService $paymentService
    ) {}

    public function schedules(): AnonymousResourceCollection
    {
        $schedules = PaymentSchedule::all();

        return PaymentScheduleResource::collection($schedules);
    }

    public function updateSchedule(int $id, UpdatePaymentScheduleRequest $request): PaymentScheduleResource
    {
        $schedule = PaymentSchedule::findOrFail($id);
        $schedule->update($request->validated());

        return new PaymentScheduleResource($schedule->fresh());
    }

    public function isPaymentDayToday(): JsonResponse
    {
        return response()->json([
            'is_payment_day' => PaymentSchedule::isPaymentDayToday(),
            'active_days' => PaymentSchedule::getActiveDays(),
        ]);
    }

    public function nextPaymentDate(): JsonResponse
    {
        $nextDate = $this->paymentService->getNextPaymentDate();

        return response()->json([
            'next_payment_date' => $nextDate->toIso8601String(),
            'next_payment_date_formatted' => $nextDate->format('l, d M Y'),
        ]);
    }

    public function sellerPayments(Request $request): AnonymousResourceCollection
    {
        $store = $request->user()->stores()->firstOrFail();

        $payments = $this->paymentService->getStorePayments(
            storeId: $store->id,
            perPage: (int) $request->query('per_page', 15)
        );

        return SellerPaymentResource::collection($payments);
    }

    public function sellerPendingPayments(Request $request): AnonymousResourceCollection
    {
        $store = $request->user()->stores()->firstOrFail();

        $payments = $this->paymentService->getStorePendingPayments($store->id);

        return SellerPaymentResource::collection($payments);
    }

    public function sellerCompletedPayments(Request $request): AnonymousResourceCollection
    {
        $store = $request->user()->stores()->firstOrFail();

        $payments = $this->paymentService->getStoreCompletedPayments($store->id);

        return SellerPaymentResource::collection($payments);
    }

    public function sellerPendingTotal(Request $request): JsonResponse
    {
        $store = $request->user()->stores()->firstOrFail();

        $total = $this->paymentService->getTotalPendingAmountByStore($store->id);
        $nextDate = $this->paymentService->getNextPaymentDate();

        return response()->json([
            'total_pending' => $total,
            'next_payment_date' => $nextDate->toIso8601String(),
            'next_payment_date_formatted' => $nextDate->format('l, d M Y'),
            'is_payment_day' => PaymentSchedule::isPaymentDayToday(),
        ]);
    }

    public function index(Request $request): AnonymousResourceCollection
    {
        $payments = $this->paymentService->getAllPayments(
            status: $request->query('status'),
            storeId: $request->query('store_id'),
            perPage: (int) $request->query('per_page', 15)
        );

        return SellerPaymentResource::collection($payments);
    }

    public function show(int $id): SellerPaymentResource
    {
        $payment = SellerPayment::query()
            ->with(['store', 'order'])
            ->findOrFail($id);

        return new SellerPaymentResource($payment);
    }

    public function process(int $id, Request $request): SellerPaymentResource
    {
        $request->validate([
            'payment_method' => ['required', 'string', 'max:50'],
            'reference' => ['nullable', 'string', 'max:100'],
        ]);

        $payment = $this->paymentService->processPayment(
            paymentId: $id,
            method: $request->validated('payment_method'),
            reference: $request->validated('reference')
        );

        return new SellerPaymentResource($payment);
    }

    public function cancel(int $id): SellerPaymentResource
    {
        $payment = $this->paymentService->cancelPayment($id);

        return new SellerPaymentResource($payment);
    }

    public function reschedule(int $id, Request $request): SellerPaymentResource
    {
        $request->validate([
            'scheduled_for' => ['required', 'date', 'after:now'],
        ]);

        $payment = $this->paymentService->reschedulePayment(
            paymentId: $id,
            newDate: \Carbon\Carbon::parse($request->validated('scheduled_for'))
        );

        return new SellerPaymentResource($payment);
    }
}
