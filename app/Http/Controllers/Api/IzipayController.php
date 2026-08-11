<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

/**
 * ARCHIVO: app/Http/Controllers/Api/IzipayController.php
 *
 * CORRECCIÓN PRINCIPAL en webhook():
 *   Izipay envía el webhook como application/x-www-form-urlencoded.
 *   Para verificar el hash correctamente necesitamos el string RAW de
 *   kr-answer — exactamente como llegó, sin que Laravel lo toque.
 *
 *   Usamos $request->input('kr-answer') que devuelve el string decodificado
 *   de URL pero SIN re-encodificar a JSON, que es lo que necesita hash_hmac.
 */

use App\Http\Controllers\Controller;
use App\Models\IzipayPlanTransaction;
use App\Models\Order;
use App\Models\PlanRequest;
use App\Services\IzipayBookingService;
use App\Services\IzipayService;
use App\Support\ClientIp;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

final class IzipayController extends Controller
{
    public function __construct(
        private readonly IzipayService $izipayService,
        private readonly IzipayBookingService $izipayBookingService,
    ) {}

    // ────────────────────────────────────────────────────────────────────
    // POST /api/payments/izipay/create-session
    // ────────────────────────────────────────────────────────────────────

    public function createSession(Request $request): JsonResponse
    {
        $data = $request->validate([
            'order_id' => ['required', 'integer', 'exists:orders,id'],
            'email' => ['required', 'email'],
            'cart_token' => ['nullable', 'string', 'min:8'],
        ]);

        $order = Order::findOrFail($data['order_id']);

        if ($order->user_id !== $request->user()->id) {
            return $this->forbidden('No tienes acceso a esta orden.');
        }

        if ($order->payment_status === Order::PAYMENT_STATUS_PAID) {
            return $this->error('Esta orden ya fue pagada.', 422);
        }

        if ((float) $order->total <= 0) {
            return $this->error('El total de la orden no es válido.', 422);
        }

        try {
            $transaction = $this->izipayService->createPaymentSession(
                order: $order,
                email: $data['email'],
                cartToken: $data['cart_token'] ?? '',
            );

            return $this->success([
                'form_token' => $transaction->form_token,
                'order_id' => (string) $order->id,
                'order_number' => $order->order_number,
                'amount' => (float) $order->total,
                'amount_in_cents' => $transaction->amount_in_cents,
                'currency' => 'PEN',
                'transaction_id' => $transaction->id,
            ]);
        } catch (\RuntimeException $e) {
            return $this->error($e->getMessage(), 502);
        }
    }

    // ────────────────────────────────────────────────────────────────────
    // POST /api/webhooks/izipay/order
    //
    // Izipay llama este endpoint después del pago.
    // SIN autenticación — viene de los servidores de Izipay.
    //
    // IMPORTANTE: el webhook llega como form-urlencoded, NO como JSON.
    // ────────────────────────────────────────────────────────────────────

    public function webhook(Request $request): JsonResponse
    {
        Log::info('IzipayController: webhook recibido', [
            'ip' => ClientIp::resolve($request),
            'kr-hash' => $request->input('kr-hash'),
            'content-type' => $request->header('Content-Type'),
        ]);

        $rawKrAnswer = $request->input('kr-answer', '');
        $payload = $request->all();

        // 1. Detectar si es plan
        if ($this->isPlanWebhook($rawKrAnswer, $payload)) {
            Log::info('IzipayController: webhook enrutado como plan');
            return $this->processPlanWebhook($rawKrAnswer, $payload);
        }

        // 2. Detectar si es booking u order
        $isBooking = $this->isBookingWebhook($rawKrAnswer, $payload);

        Log::info('IzipayController: webhook enrutado', [
            'type' => $isBooking ? 'booking' : 'order',
            'kr-hash' => $request->input('kr-hash'),
        ]);

        try {
            $result = $isBooking
                ? $this->izipayBookingService->processWebhook($payload, $rawKrAnswer)
                : $this->izipayService->processWebhook($payload, $rawKrAnswer);

            return response()->json($result, 200);
        } catch (\Throwable $e) {
            Log::error('IzipayController: error procesando webhook', [
                'type' => $isBooking ? 'booking' : 'order',
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error interno procesando el webhook',
            ], 200);
        }
    }

    private function isPlanWebhook(string $rawKrAnswer, array $payload): bool
    {
        $answer = $this->decodeKrAnswer($rawKrAnswer ?: ($payload['kr-answer'] ?? ''));
        if (! $answer) {
            return false;
        }

        // Estrategia 1: metadata contiene plan_request_id
        $metadata = $answer['transactions'][0]['metadata']
            ?? $answer['metadata']
            ?? [];

        if (isset($metadata['plan_request_id'])) {
            return true;
        }

        // Estrategia 2: orderId empieza con 'PLAN-'
        $izipayOrderId = $answer['orderDetails']['orderId']
            ?? $answer['orderId']
            ?? '';

        if (str_starts_with($izipayOrderId, 'PLAN-')) {
            return true;
        }

        return false;
    }

    private function processPlanWebhook(string $rawKrAnswer, array $payload): JsonResponse
    {
        $answer = $this->decodeKrAnswer($rawKrAnswer ?: ($payload['kr-answer'] ?? ''));
        if (! $answer) {
            return response()->json(['success' => false, 'message' => 'kr-answer inválido'], 200);
        }

        $orderInfo = $answer['orderDetails'] ?? [];
        $izipayOrderId = $orderInfo['orderId'] ?? $answer['orderId'] ?? '';
        $orderStatus = $answer['orderStatus'] ?? '';

        if (empty($izipayOrderId)) {
            return response()->json(['success' => false, 'message' => 'orderId no encontrado'], 200);
        }

        $transaction = IzipayPlanTransaction::where('izipay_order_id', $izipayOrderId)->first();
        if (! $transaction) {
            Log::warning('IzipayController: IzipayPlanTransaction no encontrada', [
                'izipay_order_id' => $izipayOrderId,
            ]);
        }

        $planRequest = $transaction
            ? $transaction->planRequest
            : PlanRequest::where('izipay_order_id', $izipayOrderId)
                ->where('status', PlanRequest::STATUS_PENDING)
                ->first();

        if (! $planRequest) {
            Log::warning('IzipayController: PlanRequest no encontrado', [
                'izipay_order_id' => $izipayOrderId,
            ]);
            return response()->json(['success' => false, 'message' => 'Solicitud no encontrada'], 200);
        }

        $txStatus = in_array($orderStatus, ['PAID', 'AUTHORISED', 'CAPTURED', 'ACCEPTED'], true)
            ? 'paid'
            : (in_array($orderStatus, ['FAILED', 'EXPIRED'], true) ? 'failed' : null);

        $transactionData = [
            'transaction_status' => $orderStatus,
            'izipay_response' => $answer,
            'kr_hash' => $payload['kr-hash'] ?? null,
        ];

        if ($txStatus === 'paid') {
            $txFirstTransaction = $answer['transactions'][0] ?? [];
            $transactionData['status'] = 'paid';
            $transactionData['transaction_uuid'] = $txFirstTransaction['uuid'] ?? null;
            $transactionData['payment_method_type'] = $txFirstTransaction['paymentMethodType'] ?? null;
            $transactionData['card_brand'] = $txFirstTransaction['cardDetails']['brand'] ?? null;
            $transactionData['card_last4'] = $txFirstTransaction['cardDetails']['pan'] ?? null;
        } elseif ($txStatus === 'failed') {
            $transactionData['status'] = 'failed';
            $transactionData['error_code'] = $answer['errorCode'] ?? 'PAYMENT_FAILED';
            $transactionData['error_message'] = $answer['errorMessage'] ?? 'Pago rechazado';
        }

        if ($transaction) {
            $transaction->update($transactionData);
        }

        if ($txStatus === 'paid') {
            $planRequest->update([
                'payment_status' => PlanRequest::PAYMENT_STATUS_PAID,
            ]);

            $planRequestController = app(PlanRequestController::class);
            $planRequestController->approvePlanRequest($planRequest, null);

            Log::info('IzipayController: plan activado por webhook', [
                'plan_request_id' => $planRequest->id,
                'izipay_order_id' => $izipayOrderId,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Pago confirmado y plan activado',
            ]);
        }

        if ($txStatus === 'failed') {
            $planRequest->update([
                'payment_status' => PlanRequest::PAYMENT_STATUS_FAILED,
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Pago fallido',
            ]);
        }

        return response()->json([
            'success' => true,
            'message' => 'Estado de pago actualizado',
        ]);
    }

    /**
     * Detecta si el webhook corresponde a un pago de booking.
     * Usa dos estrategias:
     *  1. metadata.type === 'booking' (enviado en createSession)
     *  2. orderId empieza con 'BKG-' (formato de booking)
     */
    private function isBookingWebhook(string $rawKrAnswer, array $payload): bool
    {
        $answer = $this->decodeKrAnswer($rawKrAnswer ?: ($payload['kr-answer'] ?? ''));
        if (! $answer) {
            return false;
        }

        // Estrategia 1: metadata.type === 'booking'
        $metadata = $answer['transactions'][0]['metadata']
            ?? $answer['metadata']
            ?? [];

        if (($metadata['type'] ?? '') === 'booking') {
            return true;
        }

        // Estrategia 2: orderId empieza con 'BKG-'
        $izipayOrderId = $answer['orderDetails']['orderId']
            ?? $answer['orderId']
            ?? '';

        if (str_starts_with($izipayOrderId, 'BKG-')) {
            return true;
        }

        return false;
    }

    private function decodeKrAnswer(string $krAnswer): ?array
    {
        if (empty($krAnswer)) {
            return null;
        }

        $decoded = json_decode($krAnswer, true);
        if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
            return $decoded;
        }

        $base64 = base64_decode($krAnswer, true);
        if ($base64 !== false) {
            $decoded = json_decode($base64, true);
            if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                return $decoded;
            }
        }

        return null;
    }

    // ────────────────────────────────────────────────────────────────────
    // GET /api/payments/izipay/status/{orderId}
    // ────────────────────────────────────────────────────────────────────

    public function status(Request $request, int $orderId): JsonResponse
    {
        $order = Order::findOrFail($orderId);

        if ($order->user_id !== $request->user()->id) {
            return $this->forbidden('No tienes acceso a esta orden.');
        }

        $transaction = $order->izipayTransactions()->latest()->first();

        return $this->success([
            'order_id' => (string) $order->id,
            'order_number' => $order->order_number,
            'payment_status' => $order->payment_status,
            'total' => (float) $order->total,
            'transaction' => $transaction ? [
                'id' => $transaction->id,
                'status' => $transaction->status,
                'transaction_status' => $transaction->transaction_status,
                'payment_method_type' => $transaction->payment_method_type,
                'card_brand' => $transaction->card_brand,
                'card_last4' => $transaction->card_last4,
                'updated_at' => $transaction->updated_at?->toIso8601String(),
            ] : null,
        ]);
    }
}
