<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Order;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

final class IzipayService
{
    private const MOCK_PREFIX = 'MOCK-';

    public function __construct(
        private readonly bool $mock,
        private readonly string $apiUrl,
        private readonly string $publicKey,
        private readonly string $privateKey,
        private readonly string $username,
        private readonly string $password,
        private readonly string $hmacKey,
        private readonly string $shopId,
    ) {}

    public static function fromConfig(): self
    {
        return new self(
            mock:      (bool) config('services.izipay.mock', true),
            apiUrl:    (string) config('services.izipay.api_url', 'https://api.micuentaweb.pe/api-payment'),
            publicKey: (string) config('services.izipay.public_key', ''),
            privateKey: (string) config('services.izipay.private_key', ''),
            username:  (string) config('services.izipay.username', ''),
            password:  (string) config('services.izipay.password', ''),
            hmacKey:   (string) config('services.izipay.hmac_key', ''),
            shopId:    (string) config('services.izipay.shop_id', ''),
        );
    }

    public function isMock(): bool
    {
        return $this->mock || empty($this->publicKey) || empty($this->privateKey);
    }

    public function getPublicKey(): string
    {
        return $this->publicKey;
    }

    /**
     * Inicializa un pago en Izipay y devuelve los datos para el frontend.
     */
    public function initPayment(Order $order): array
    {
        if ($this->isMock()) {
            return $this->mockInit($order);
        }

        return $this->realInit($order);
    }

    /**
     * Confirma el pago de una orden (modo MOCK).
     */
    public function mockConfirmPayment(Order $order): array
    {
        Log::info('[IzipayService:MOCK] Confirmando pago simulado', [
            'order_id'     => $order->id,
            'order_number' => $order->order_number,
        ]);

        return [
            'success'         => true,
            'order_id'        => (string) $order->id,
            'transaction_id'   => self::MOCK_PREFIX . str_pad((string) random_int(0, 999999999), 9, '0', STR_PAD_LEFT),
            'transaction_state' => 'PAID',
            'mock'            => true,
        ];
    }

    private function mockInit(Order $order): array
    {
        Log::info('[IzipayService:MOCK] Inicializando pago simulado', [
            'order_id'     => $order->id,
            'order_number' => $order->order_number,
            'total'        => $order->total,
        ]);

        return [
            'mode'       => 'mock',
            'order_id'   => (string) $order->id,
            'public_key' => 'MOCK_PUBLIC_KEY',
            'form_token' => self::MOCK_PREFIX . bin2hex(random_bytes(16)),
            'amount'     => (float) $order->total,
        ];
    }

    private function realInit(Order $order): array
    {
        $url = rtrim($this->apiUrl, '/') . '/V4/Charge/CreatePayment';

        $amountCents = (int) round((float) $order->total * 100);

        $payload = [
            'amount'             => $amountCents,
            'currency'           => 'PEN',
            'orderId'            => $order->order_number,
            'customer'           => [
                'email' => $order->shipping_email ?? $order->user?->email ?? '',
            ],
            'transactionOptions' => [
                'cardOptions' => [
                    'captureDelay' => 0,
                    'manualValidation' => false,
                ],
            ],
        ];

        Log::info('[IzipayService] Inicializando pago real', [
            'order_id'     => $order->id,
            'amount_cents' => $amountCents,
        ]);

        try {
            $response = Http::withBasicAuth($this->username, $this->password)
                ->withHeaders(['Content-Type' => 'application/json'])
                ->timeout(15)
                ->post($url, $payload);

            if ($response->failed()) {
                Log::error('[IzipayService] Error al crear pago', [
                    'status' => $response->status(),
                    'body'   => $response->body(),
                ]);

                throw new \RuntimeException(
                    'Izipay respondió con HTTP ' . $response->status() . ': ' . $response->body()
                );
            }

            $data = $response->json();

            Log::info('[IzipayService] Pago creado exitosamente', [
                'formToken' => ($data['answer']['formToken'] ?? '') !== '' ? '***' : 'vacio',
            ]);

            return [
                'mode'       => 'izipay',
                'order_id'   => (string) $order->id,
                'public_key' => $this->publicKey,
                'form_token' => $data['answer']['formToken'] ?? '',
                'amount'     => (float) $order->total,
            ];
        } catch (\Throwable $e) {
            Log::error('[IzipayService] Excepción al crear pago', [
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }
}
