<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

/**
 * ARCHIVO: app/Http/Controllers/Api/CulqiController.php
 */

use App\Http\Controllers\Controller;
use App\Http\Requests\Payment\CulqiChargeRequest;
use App\Models\Order;
use App\Services\CulqiService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

final class CulqiController extends Controller
{
    public function __construct(
        private readonly CulqiService $culqiService
    ) {}

    // ────────────────────────────────────────────────────────────────────
    // POST /api/payments/culqi/charge
    // El frontend envía: order_id + culqi_token + email
    // ────────────────────────────────────────────────────────────────────

    public function charge(CulqiChargeRequest $request): JsonResponse
    {
        $user  = $request->user();
        $data  = $request->validated();

        // 1. Obtener la orden y verificar que pertenece al usuario
        $order = Order::with('items.product')->findOrFail($data['order_id']);

        if ($order->user_id !== $user->id) {
            return $this->forbidden('No tienes acceso a esta orden.');
        }

        // 2. Verificar que la orden aún está pendiente de pago
        if ($order->payment_status === Order::PAYMENT_STATUS_PAID) {
            return $this->error('Esta orden ya fue pagada.', 422);
        }

        if ($order->payment_status === Order::PAYMENT_STATUS_FAILED) {
            // Permitir reintentar — Culqi generó un nuevo token
            // El registro anterior queda en culqi_transactions como fallido
        }

        // 3. Verificar que el total es mayor a 0
        if ((float) $order->total <= 0) {
            return $this->error('El total de la orden no es válido.', 422);
        }

        // 4. Hacer el cobro
        $transaction = $this->culqiService->charge(
            order: $order,
            token: $data['culqi_token'],
            email: $data['email'],
        );

        // 5. Responder según resultado
        if ($transaction->isPaid()) {
            return $this->success([
                'paid'           => true,
                'order_id'       => (string) $order->id,
                'order_number'   => $order->order_number,
                'amount'         => (float) $transaction->amount,
                'currency'       => $transaction->currency,
                'charge_id'      => $transaction->culqi_charge_id,
                'card_brand'     => $transaction->card_brand,
                'card_last_four' => $transaction->card_last_four,
                'payment_status' => $order->fresh()->payment_status,
                'message'        => '¡Pago realizado con éxito!',
            ]);
        }

        // Cobro rechazado — dar mensaje amigable al usuario
        return $this->error(
            $transaction->error_message ?? 'No se pudo procesar el pago. Verifica los datos de tu tarjeta.',
            422,
            [
                'paid'       => false,
                'error_code' => $transaction->error_code,
            ]
        );
    }

    // ────────────────────────────────────────────────────────────────────
    // POST /api/webhooks/culqi   (público — sin auth, Culqi lo llama)
    // ────────────────────────────────────────────────────────────────────

    public function webhook(Request $request): JsonResponse
    {
        // 1. Verificar que la petición viene de Culqi con el header de seguridad
        $culqiSignature = $request->header('x-culqi-rsa-signature');

        if (! $this->isValidCulqiSignature($request, $culqiSignature)) {
            Log::warning('Culqi webhook: firma inválida', [
                'ip'        => $request->ip(),
                'signature' => $culqiSignature,
            ]);
            return response()->json(['message' => 'Firma inválida'], 401);
        }

        $payload = $request->all();

        Log::info('Culqi webhook recibido', [
            'type'      => $payload['type'] ?? 'unknown',
            'charge_id' => $payload['data']['id'] ?? null,
        ]);

        // 2. Procesar el evento
        try {
            $this->culqiService->processWebhookEvent($payload);
        } catch (\Throwable $e) {
            Log::error('Culqi webhook error procesando evento', [
                'error'   => $e->getMessage(),
                'payload' => $payload,
            ]);
            // Devolver 200 igual — si devuelves 500, Culqi reintenta
            return response()->json(['message' => 'Error procesando evento'], 200);
        }

        // Culqi espera un 200 para marcar el webhook como entregado
        return response()->json(['message' => 'ok'], 200);
    }

    // ────────────────────────────────────────────────────────────────────
    // GET /api/payments/culqi/status/{orderId}
    // El frontend consulta el estado del pago de una orden
    // ────────────────────────────────────────────────────────────────────

    public function status(Request $request, int $orderId): JsonResponse
    {
        $order = Order::findOrFail($orderId);

        if ($order->user_id !== $request->user()->id) {
            return $this->forbidden('No tienes acceso a esta orden.');
        }

        // Obtener la transacción más reciente de esta orden
        $transaction = $order->culqiTransactions()->latest()->first();

        return $this->success([
            'order_id'       => (string) $order->id,
            'order_number'   => $order->order_number,
            'payment_status' => $order->payment_status,
            'total'          => (float) $order->total,
            'transaction'    => $transaction ? [
                'charge_id'      => $transaction->culqi_charge_id,
                'status'         => $transaction->status,
                'card_brand'     => $transaction->card_brand,
                'card_last_four' => $transaction->card_last_four,
                'paid_at'        => $transaction->updated_at?->toIso8601String(),
            ] : null,
        ]);
    }

    // ────────────────────────────────────────────────────────────────────
    // Verificación de firma del webhook de Culqi
    // ────────────────────────────────────────────────────────────────────

    private function isValidCulqiSignature(Request $request, ?string $signature): bool
    {
        // En modo test, Culqi no envía firma RSA — permitir siempre
        if (config('services.culqi.mode') === 'test') {
            return true;
        }

        // En producción verificar la firma RSA con la llave pública de Culqi
        // Documentación: https://docs.culqi.com/#/webhooks/seguridad
        if (! $signature) {
            return false;
        }

        $publicKey = config('services.culqi.webhook_public_key', '');
        $body      = $request->getContent();

        $result = openssl_verify(
            $body,
            base64_decode($signature),
            $publicKey,
            OPENSSL_ALGO_SHA256
        );

        return $result === 1;
    }
}
