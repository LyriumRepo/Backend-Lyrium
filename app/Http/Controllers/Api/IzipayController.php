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
use App\Models\Order;
use App\Services\IzipayBookingService;
use App\Services\IzipayService;
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
            'ip' => $request->ip(),
            'kr-hash' => $request->input('kr-hash'),
            'content-type' => $request->header('Content-Type'),
        ]);

        $rawKrAnswer = $request->input('kr-answer', '');
        $payload = $request->all();

        // Detectar si es booking o order
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
