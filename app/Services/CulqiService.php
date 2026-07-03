<?php

declare(strict_types=1);

namespace App\Services;

/**
 * ARCHIVO: app/Services/CulqiService.php
 */

use App\Events\OrderPaymentConfirmed;
use App\Models\CulqiTransaction;
use App\Models\Order;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

final class CulqiService
{
    private const BASE_URL = 'https://api.culqi.com/v2';

    private string $secretKey;

    private string $mode;

    public function __construct()
    {
        // FIX: castear a string para que declare(strict_types=1) no se queje
        // config() puede devolver null si la clave no existe en services.php
        $this->secretKey = (string) config('services.culqi.secret_key', '');
        $this->mode = (string) config('services.culqi.mode', 'test');
    }

    // ── Método principal: cobrar al cliente ───────────────────────────────

    public function charge(Order $order, string $token, string $email): CulqiTransaction
    {
        $amountInCents = CulqiTransaction::toCents((float) $order->total);

        $transaction = CulqiTransaction::create([
            'order_id' => $order->id,
            'user_id' => $order->user_id,
            'culqi_token' => $token,
            'status' => 'pending',
            'amount' => $order->total,
            'amount_in_cents' => $amountInCents,
            'currency' => 'PEN',
            'email' => $email,
            'mode' => $this->mode,
            'source' => 'checkout',
        ]);

        try {
            // ── Normalizar datos de antifraud ────────────────────────────
            $nameParts = explode(' ', trim($order->shipping_name ?? 'Cliente'));
            $firstName = $nameParts[0];
            $lastName = $nameParts[1] ?? $nameParts[0]; // Culqi requiere lastName no vacío

            // Culqi exige teléfono solo dígitos, 7-15 caracteres
            $phone = preg_replace('/\D/', '', $order->shipping_phone ?? '');
            if (strlen($phone) < 7) {
                $phone = '999999999';
            }

            // Dirección máximo 100 chars, ciudad máximo 50
            $address = substr(trim($order->shipping_address ?? 'Lima'), 0, 100);
            $city = substr(trim($order->shipping_city ?? 'Lima'), 0, 50);

            // ── Armar payload ─────────────────────────────────────────────
            $payload = [
                'amount' => (int) $amountInCents,
                'currency_code' => 'PEN',
                'email' => $email,
                'source_id' => $token,
                'description' => "Orden #{$order->order_number} - Lyrium BioMarketplace",
                'antifraud_details' => [
                    'first_name' => $firstName,
                    'last_name' => $lastName,
                    'email' => $email,
                    'phone_number' => $phone,
                    'address' => $address,
                    'address_city' => $city,
                ],
                'metadata' => [
                    'order_id' => (string) $order->id,
                    'order_number' => $order->order_number,
                    'user_id' => (string) $order->user_id,
                ],
            ];

            Log::info('Culqi charge payload', $payload);

            $response = Http::withToken($this->secretKey)
                ->withHeaders(['Content-Type' => 'application/json'])
                ->timeout(30)
                ->post(self::BASE_URL.'/charges', $payload);

            $data = $response->json();

            Log::info('Culqi charge response', [
                'order_id' => $order->id,
                'status' => $response->status(),
                'charge_id' => $data['id'] ?? null,
                'response' => $data,  // respuesta completa para debug
            ]);

            // ── Culqi aprobó el cobro ─────────────────────────────────────
            if ($response->successful() && isset($data['id'])) {
                $card = $data['source'] ?? [];

                $transaction->update([
                    'culqi_charge_id' => $data['id'],
                    'status' => 'paid',
                    'card_brand' => $card['iin']['card_brand'] ?? null,
                    'card_last_four' => $card['last_four'] ?? null,
                    'card_exp_month' => $card['iin']['exp_month'] ?? null,
                    'card_exp_year' => $card['iin']['exp_year'] ?? null,
                    'culqi_response' => $data,
                ]);

                $order->update([
                    'payment_status' => Order::PAYMENT_STATUS_PAID,
                    'payment_method' => 'culqi_'.($card['iin']['card_brand'] ?? 'card'),
                ]);

                event(new OrderPaymentConfirmed(
                    order: $order,
                    paymentMethod: 'culqi',
                ));

                app(AuditService::class)->record(
                    event: 'payments.transaction.completed',
                    module: 'payments',
                    description: "Pago confirmado via Culqi para orden #{$order->order_number}",
                    auditable: $order,
                    newValues: ['payment_status' => Order::PAYMENT_STATUS_PAID],
                    success: true,
                    source: AuditService::SOURCE_WEB,
                    correlationId: (string) $order->id,
                    metadata: [
                        'culqi_charge_id' => $data['id'],
                        'payment_method' => 'culqi',
                    ],
                );

                return $transaction;
            }

            // ── Culqi rechazó el cobro ────────────────────────────────────
            $errorCode = $data['code'] ?? $data['type'] ?? 'unknown_error';
            $errorMessage = $data['user_message'] ?? $data['merchant_message'] ?? 'Error al procesar el pago.';

            $transaction->update([
                'status' => 'failed',
                'error_code' => $errorCode,
                'error_message' => $errorMessage,
                'culqi_response' => $data,
            ]);

            $order->update(['payment_status' => Order::PAYMENT_STATUS_FAILED]);

            return $transaction;
        } catch (\Illuminate\Http\Client\ConnectionException $e) {
            Log::error('Culqi connection error', [
                'order_id' => $order->id,
                'error' => $e->getMessage(),
            ]);

            $transaction->update([
                'status' => 'failed',
                'error_code' => 'connection_error',
                'error_message' => 'No se pudo conectar con el servidor de pagos. Intenta nuevamente.',
            ]);

            $order->update(['payment_status' => Order::PAYMENT_STATUS_FAILED]);

            return $transaction;
        } catch (\Throwable $e) {
            Log::error('Culqi unexpected error', [
                'order_id' => $order->id,
                'error' => $e->getMessage(),
            ]);

            $transaction->update([
                'status' => 'failed',
                'error_code' => 'unexpected_error',
                'error_message' => 'Error inesperado al procesar el pago.',
            ]);

            $order->update(['payment_status' => Order::PAYMENT_STATUS_FAILED]);

            return $transaction;
        }
    }

    // ── Consultar un cargo en Culqi ───────────────────────────────────────

    public function getCharge(string $chargeId): ?array
    {
        try {
            $response = Http::withToken($this->secretKey)
                ->timeout(15)
                ->get(self::BASE_URL.'/charges/'.$chargeId);

            return $response->successful() ? $response->json() : null;
        } catch (\Throwable) {
            return null;
        }
    }

    // ── Procesar evento de Webhook ────────────────────────────────────────

    public function processWebhookEvent(array $payload): void
    {
        $eventType = $payload['type'] ?? null;
        $data = $payload['data'] ?? [];
        $chargeId = $data['id'] ?? null;

        app(AuditService::class)->record(
            event: 'payments.webhook.received',
            module: 'payments',
            description: 'Webhook Culqi recibido',
            source: AuditService::SOURCE_API,
            correlationId: $chargeId,
            metadata: ['event_type' => $eventType],
        );

        if (! $chargeId) {
            Log::warning('Culqi webhook sin charge_id', $payload);

            return;
        }

        $transaction = CulqiTransaction::where('culqi_charge_id', $chargeId)->first();

        if (! $transaction) {
            $orderId = $data['metadata']['order_id'] ?? null;
            if ($orderId) {
                $transaction = CulqiTransaction::where('order_id', $orderId)->latest()->first();
            }
        }

        if (! $transaction) {
            Log::warning('Culqi webhook: transacción no encontrada', ['charge_id' => $chargeId]);

            return;
        }

        match ($eventType) {
            'charge.succeeded' => $this->handleChargeSucceeded($transaction, $data),
            'charge.failed' => $this->handleChargeFailed($transaction, $data),
            'refund.succeeded' => $this->handleRefundSucceeded($transaction, $data),
            default => Log::info('Culqi webhook: evento no manejado', ['type' => $eventType]),
        };
    }

    // ── Handlers privados ─────────────────────────────────────────────────

    private function handleChargeSucceeded(CulqiTransaction $transaction, array $data): void
    {
        if ($transaction->isPaid()) {
            return;
        }

        $transaction->update([
            'culqi_charge_id' => $data['id'],
            'status' => 'paid',
            'culqi_response' => $data,
            'source' => 'webhook',
        ]);

        $transaction->order->update(['payment_status' => Order::PAYMENT_STATUS_PAID]);

        event(new OrderPaymentConfirmed(
            order: $transaction->order,
            paymentMethod: 'culqi',
        ));

        Log::info('Culqi webhook: cargo confirmado', ['order_id' => $transaction->order_id]);
    }

    private function handleChargeFailed(CulqiTransaction $transaction, array $data): void
    {
        $transaction->update([
            'status' => 'failed',
            'error_code' => $data['code'] ?? 'webhook_failed',
            'error_message' => $data['user_message'] ?? 'Pago rechazado.',
            'culqi_response' => $data,
            'source' => 'webhook',
        ]);

        $transaction->order->update(['payment_status' => Order::PAYMENT_STATUS_FAILED]);
    }

    private function handleRefundSucceeded(CulqiTransaction $transaction, array $data): void
    {
        $transaction->update([
            'status' => 'refunded',
            'culqi_response' => $data,
            'source' => 'webhook',
        ]);

        $transaction->order->update(['payment_status' => Order::PAYMENT_STATUS_REFUNDED]);
    }
}
