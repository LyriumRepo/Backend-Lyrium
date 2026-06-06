<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\CreateBookingSessionRequest;
use App\Services\IzipayBookingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

final class IzipayBookingController extends Controller
{
    public function __construct(
        private readonly IzipayBookingService $izipayBookingService,
    ) {}

    public function createSession(CreateBookingSessionRequest $request): JsonResponse
    {
        $data = $request->validated();

        try {
            $transaction = $this->izipayBookingService->createSession(
                userId: $request->user()->id,
                serviceId: (int) $data['service_id'],
                email: $data['email'],
                bookingData: $data,
            );

            return response()->json([
                'success' => true,
                'form_token' => $transaction->form_token,
                'transaction_id' => $transaction->id,
                'amount_in_cents' => $transaction->amount_in_cents,
                'currency' => $transaction->currency,
            ]);
        } catch (\RuntimeException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 502);
        }
    }

    public function updateBookingData(Request $request, int $transactionId): JsonResponse
    {
        $data = $request->validate([
            'schedule_id' => ['required', 'integer', 'exists:service_schedules,id'],
            'specialist_id' => ['nullable', 'integer', 'exists:specialists,id'],
            'appointment_date' => ['required', 'date', 'after:now'],
            'start_time' => ['required', 'string', 'date_format:H:i'],
            'customer_notes' => ['nullable', 'string', 'max:1000'],
        ]);

        $transaction = \App\Models\IzipayBookingTransaction::findOrFail($transactionId);

        if ($transaction->user_id !== $request->user()->id) {
            return response()->json(['message' => 'No autorizado'], 403);
        }

        $appointmentDate = \Carbon\Carbon::parse($data['appointment_date'].' '.$data['start_time']);

        $transaction->update([
            'schedule_id' => $data['schedule_id'],
            'specialist_id' => $data['specialist_id'] ?? null,
            'appointment_date' => $appointmentDate,
            'customer_notes' => $data['customer_notes'] ?? null,
        ]);

        return response()->json(['success' => true]);
    }

    public function confirmBooking(Request $request): JsonResponse
    {
        $data = $request->validate([
            'transaction_id' => ['required', 'integer', 'exists:izipay_booking_transactions,id'],
        ]);

        $result = $this->izipayBookingService->confirmBooking(
            transactionId: (int) $data['transaction_id'],
            userId: $request->user()->id,
        );

        return response()->json($result, $result['success'] ? 200 : 422);
    }

    public function status(Request $request, int $transactionId): JsonResponse
    {
        $transaction = $this->izipayBookingService->getStatus($transactionId);

        if (! $transaction || $transaction->user_id !== $request->user()->id) {
            return response()->json(['message' => 'No encontrado'], 404);
        }

        return response()->json([
            'success' => true,
            'status' => $transaction->status,
            'transaction_status' => $transaction->transaction_status,
            'payment_method_type' => $transaction->payment_method_type,
            'card_brand' => $transaction->card_brand,
            'card_last4' => $transaction->card_last4,
            'booking_id' => $transaction->booking_id,
            'error_message' => $transaction->error_message,
        ]);
    }

    public function webhook(Request $request): JsonResponse
    {
        Log::info('IzipayBookingController: webhook recibido');

        $rawKrAnswer = $request->input('kr-answer', '');
        $payload = $request->all();

        try {
            $result = $this->izipayBookingService->processWebhook($payload, $rawKrAnswer);

            return response()->json($result, 200);
        } catch (\Throwable $e) {
            Log::error('IzipayBookingController: error webhook', [
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error interno',
            ], 200);
        }
    }
}
