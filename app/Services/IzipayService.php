<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\IzipayOrderTransaction;
use App\Models\Order;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

final class IzipayService
{
    private const API_URL = 'https://api.micuentaweb.pe/api-payment/V4/Charge/CreatePayment';
    private const MOCK_PREFIX = 'MOCK-';

    private readonly bool $mock;
    private readonly string $apiUrl;
    private readonly string $publicKey;
    private readonly string $privateKey;
    private readonly string $username;
    private readonly string $password;
    private readonly string $hmacKey;
    private readonly string $shopId;
    private readonly string $userId;
    private readonly string $mode;
    private readonly string $hashKey;

    public function __construct(
        bool $mock = true,
        string $apiUrl = 'https://api.micuentaweb.pe/api-payment',
        string $publicKey = '',
        string $privateKey = '',
        string $username = '',
        string $password = '',
        string $hmacKey = '',
        string $shopId = '',
        string $userId = '',
        string $mode = 'test',
        string $hashKey = '',
    ) {
        $this->mock = $mock;
        $this->apiUrl = $apiUrl;
        $this->publicKey = $publicKey;
        $this->privateKey = $privateKey;
        $this->username = $username;
        $this->password = $password;
        $this->hmacKey = $hmacKey;
        $this->shopId = $shopId;
        $this->userId = $userId;
        $this->mode = $mode;
        $this->hashKey = $hashKey;
    }

    public static function fromConfig(): self
    {
        return new self(
            mock:       (bool) config('services.izipay.mock', true),
            apiUrl:     (string) config('services.izipay.api_url', 'https://api.micuentaweb.pe/api-payment'),
            publicKey:  (string) config('services.izipay.public_key', ''),
            privateKey: (string) config('services.izipay.private_key', ''),
            username:   (string) config('services.izipay.username', ''),
            password:   (string) config('services.izipay.password', ''),
            hmacKey:    (string) config('services.izipay.hmac_key', ''),
            shopId:     (string) config('services.izipay.shop_id', ''),
            userId:     (string) config('services.izipay.user_id', ''),
            mode:       (string) config('services.izipay.mode', 'test'),
            hashKey:    (string) config('services.izipay.hash_key', ''),
        );
    }

    // ── Modo simulado ────────────────────────────────────────────────────

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
            'success'          => true,
            'order_id'         => (string) $order->id,
            'transaction_id'   => self::MOCK_PREFIX . str_pad((string) random_int(0, 999999999), 9, '0', STR_PAD_LEFT),
            'transaction_state' => 'PAID',
            'mock'             => true,
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

    // ── Modo real (initPayment) ──────────────────────────────────────────

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

    // ── Crear sesión de pago (formToken) ─────────────────────────────────

    public function createPaymentSession(Order $order, string $email): IzipayOrderTransaction
    {
        $amountCents   = IzipayOrderTransaction::toCents($order->total);
        $izipayOrderId = IzipayOrderTransaction::generateIzipayOrderId($order->id);

        $credentials = base64_encode("{$this->userId}:{$this->password}");

        $transaction = IzipayOrderTransaction::create([
            'order_id'        => $order->id,
            'user_id'         => $order->user_id,
            'izipay_order_id' => $izipayOrderId,
            'status'          => 'pending',
            'amount_in_cents' => $amountCents,
            'currency'        => 'PEN',
            'mode'            => $this->mode,
        ]);

        $payload = [
            'amount'   => $amountCents,
            'currency' => 'PEN',
            'orderId'  => $izipayOrderId,
            'customer' => ['email' => $email],
            'metadata' => [
                'order_id'     => (string) $order->id,
                'order_number' => $order->order_number,
            ],
        ];

        Log::info('IzipayService: creando sesión de pago', [
            'order_id'        => $order->id,
            'izipay_order_id' => $izipayOrderId,
            'amount_cents'    => $amountCents,
        ]);

        try {
            $response = Http::withHeaders([
                'Authorization' => "Basic {$credentials}",
                'Content-Type'  => 'application/json',
            ])
                ->timeout(30)
                ->post(self::API_URL, $payload);

            $data = $response->json();

            Log::info('IzipayService: respuesta recibida', [
                'status'   => $data['status'] ?? null,
                'order_id' => $order->id,
            ]);

            if (($data['status'] ?? '') === 'SUCCESS' && isset($data['answer']['formToken'])) {
                $transaction->update([
                    'form_token'      => $data['answer']['formToken'],
                    'izipay_response' => $data,
                ]);

                return $transaction;
            }

            $errorMsg = $data['answer']['errorMessage']
                ?? $data['answer']['detailedErrorMessage']
                ?? 'Error al crear la sesión de pago con Izipay.';

            $transaction->update([
                'status'          => 'failed',
                'error_code'      => (string) ($data['answer']['errorCode'] ?? 'IZIPAY_ERROR'),
                'error_message'   => $errorMsg,
                'izipay_response' => $data,
            ]);

            throw new \RuntimeException($errorMsg);
        } catch (ConnectionException $e) {
            $transaction->update([
                'status'        => 'failed',
                'error_code'    => 'CONNECTION_ERROR',
                'error_message' => 'No se pudo conectar con Izipay.',
            ]);

            Log::error('IzipayService: error de conexión', [
                'order_id' => $order->id,
                'error'    => $e->getMessage(),
            ]);

            throw new \RuntimeException('No se pudo conectar con el servidor de pagos. Intenta nuevamente.');
        }
    }

    // ── Tokenización de tarjetas ─────────────────────────────────────────

    /**
     * Crea una sesión de tokenización en Izipay.
     * Devuelve formToken + publicKey para renderizar el Smart Form.
     */
    public function createTokenizationSession(string $email): array
    {
        if ($this->isMock()) {
            return [
                'mode'       => 'mock',
                'public_key' => 'MOCK_PUBLIC_KEY',
                'form_token' => self::MOCK_PREFIX . 'TOKENIZE-' . bin2hex(random_bytes(16)),
            ];
        }

        $url = rtrim($this->apiUrl, '/') . '/V4/Token/Create';
        $credentials = base64_encode("{$this->userId}:{$this->password}");

        Log::info('IzipayService: creando sesión de tokenización');

        try {
            $response = Http::withHeaders([
                'Authorization' => "Basic {$credentials}",
                'Content-Type'  => 'application/json',
            ])
                ->timeout(30)
                ->post($url);

            $data = $response->json();

            if (($data['status'] ?? '') === 'SUCCESS' && isset($data['answer']['formToken'])) {
                Log::info('IzipayService: sesión de tokenización creada');
                return [
                    'mode'       => 'izipay',
                    'public_key' => $this->publicKey,
                    'form_token' => $data['answer']['formToken'],
                ];
            }

            $errorMsg = $data['answer']['errorMessage']
                ?? $data['answer']['detailedErrorMessage']
                ?? 'Error al crear sesión de tokenización.';

            Log::error('IzipayService: error en tokenización', [
                'error' => $errorMsg,
            ]);

            throw new \RuntimeException($errorMsg);
        } catch (ConnectionException $e) {
            Log::error('IzipayService: error de conexión en tokenización', [
                'error' => $e->getMessage(),
            ]);
            throw new \RuntimeException('No se pudo conectar con el servidor de pagos.');
        }
    }

    /**
     * Procesa un pago usando un cardToken guardado (sin Smart Form).
     * Devuelve los datos de la transacción.
     */
    public function chargeWithToken(Order $order, string $cardToken, string $email): array
    {
        if ($this->isMock()) {
            return [
                'success'          => true,
                'order_id'         => (string) $order->id,
                'transaction_id'   => self::MOCK_PREFIX . 'TOKEN-' . str_pad((string) random_int(0, 999999999), 9, '0', STR_PAD_LEFT),
                'transaction_state' => 'PAID',
                'mock'             => true,
            ];
        }

        $amountCents   = IzipayOrderTransaction::toCents($order->total);
        $izipayOrderId = IzipayOrderTransaction::generateIzipayOrderId($order->id);
        $credentials   = base64_encode("{$this->userId}:{$this->password}");

        $transaction = IzipayOrderTransaction::create([
            'order_id'        => $order->id,
            'user_id'         => $order->user_id,
            'izipay_order_id' => $izipayOrderId,
            'status'          => 'pending',
            'amount_in_cents' => $amountCents,
            'currency'        => 'PEN',
            'mode'            => $this->mode,
        ]);

        $payload = [
            'amount'   => $amountCents,
            'currency' => 'PEN',
            'orderId'  => $izipayOrderId,
            'customer' => ['email' => $email],
            'cardToken' => $cardToken,
            'metadata' => [
                'order_id'     => (string) $order->id,
                'order_number' => $order->order_number,
            ],
        ];

        Log::info('IzipayService: cobrando con token guardado', [
            'order_id'        => $order->id,
            'izipay_order_id' => $izipayOrderId,
            'amount_cents'    => $amountCents,
        ]);

        try {
            $response = Http::withHeaders([
                'Authorization' => "Basic {$credentials}",
                'Content-Type'  => 'application/json',
            ])
                ->timeout(30)
                ->post(self::API_URL, $payload);

            $data = $response->json();

            if (($data['status'] ?? '') === 'SUCCESS' && isset($data['answer']['formToken'])) {
                $transaction->update([
                    'form_token'      => $data['answer']['formToken'],
                    'izipay_response' => $data,
                ]);

                return [
                    'success'    => true,
                    'order_id'   => (string) $order->id,
                    'transaction'=> $transaction,
                    'form_token' => $data['answer']['formToken'],
                ];
            }

            $errorMsg = $data['answer']['errorMessage']
                ?? $data['answer']['detailedErrorMessage']
                ?? 'Error al procesar pago con token.';

            $transaction->update([
                'status'          => 'failed',
                'error_code'      => (string) ($data['answer']['errorCode'] ?? 'TOKEN_ERROR'),
                'error_message'   => $errorMsg,
                'izipay_response' => $data,
            ]);

            throw new \RuntimeException($errorMsg);
        } catch (ConnectionException $e) {
            $transaction->update([
                'status'        => 'failed',
                'error_code'    => 'CONNECTION_ERROR',
                'error_message' => 'No se pudo conectar con Izipay.',
            ]);
            throw new \RuntimeException('No se pudo conectar con el servidor de pagos.');
        }
    }

    // ── Procesar webhook de Izipay ──────────────────────────────────────

    /**
     * Procesa la notificación POST que Izipay envía después del pago.
     *
     * IMPORTANTE: $rawKrAnswer debe ser el string CRUDO de kr-answer,
     * tal como llegó en el body — sin pasar por json_decode/json_encode.
     *
     * @param  array  $payload       Datos POST parseados ($request->all())
     * @param  string $rawKrAnswer   String crudo de kr-answer
     */
    public function processWebhook(array $payload, string $rawKrAnswer = ''): array
    {
        if (! $this->verifyKrHash($payload, $rawKrAnswer)) {
            Log::warning('IzipayService webhook: firma inválida', [
                'kr_hash'         => $payload['kr-hash'] ?? null,
                'hash_key_length' => strlen($this->hashKey),
                'answer_length'   => strlen($rawKrAnswer),
            ]);

            return ['success' => false, 'message' => 'Firma inválida'];
        }

        $answer = $this->decodeKrAnswer($rawKrAnswer ?: ($payload['kr-answer'] ?? ''));

        if (! $answer) {
            return ['success' => false, 'message' => 'No se pudo decodificar kr-answer'];
        }

        $orderInfo     = $answer['orderDetails'] ?? [];
        $izipayOrderId = $orderInfo['orderId'] ?? $answer['orderId'] ?? null;

        Log::info('IzipayService webhook recibido', [
            'izipay_order_id'    => $izipayOrderId,
            'transaction_status' => $answer['orderStatus'] ?? null,
        ]);

        if (! $izipayOrderId) {
            return ['success' => false, 'message' => 'orderId no encontrado en webhook'];
        }

        $transaction = IzipayOrderTransaction::where('izipay_order_id', $izipayOrderId)
            ->latest()
            ->first();

        if (! $transaction) {
            $orderId = $answer['transactions'][0]['metadata']['order_id']
                ?? $answer['metadata']['order_id']
                ?? null;

            if ($orderId) {
                $transaction = IzipayOrderTransaction::where('order_id', $orderId)
                    ->where('status', 'pending')
                    ->latest()
                    ->first();
            }
        }

        if (! $transaction) {
            Log::warning('IzipayService webhook: transacción no encontrada', [
                'izipay_order_id' => $izipayOrderId,
            ]);
            return ['success' => false, 'message' => 'Transacción no encontrada'];
        }

        if ($transaction->isPaid()) {
            return ['success' => true, 'message' => 'Ya procesado'];
        }

        $txData            = $answer['transactions'][0] ?? [];
        $transactionStatus = $answer['orderStatus']
            ?? $txData['detailedStatus']
            ?? $txData['status']
            ?? '';

        $isPaid = in_array($transactionStatus, ['PAID', 'AUTHORISED', 'CAPTURED', 'ACCEPTED'], true);

        if ($isPaid) {
            $pan = $txData['transactionDetails']['cardDetails']['pan'] ?? null;
            $cardLast4 = $pan ? substr($pan, -4) : null;
            $transaction->update([
                'status'              => 'paid',
                'transaction_status'  => $transactionStatus,
                'transaction_uuid'    => $txData['uuid'] ?? null,
                'payment_method_type' => $txData['paymentMethodType'] ?? null,
                'card_brand'          => $txData['transactionDetails']['cardDetails']['effectiveBrand'] ?? null,
                'card_last4'          => $cardLast4,
                'kr_hash'             => $payload['kr-hash'] ?? null,
                'izipay_response'     => $answer,
            ]);

            $transaction->order->update([
                'payment_status' => Order::PAYMENT_STATUS_PAID,
                'payment_method' => 'izipay_' . strtolower($txData['paymentMethodType'] ?? 'card'),
            ]);

            Log::info('IzipayService webhook: pago confirmado ✓', [
                'order_id'  => $transaction->order_id,
                'tx_status' => $transactionStatus,
                'amount'    => $txData['amount'] ?? null,
            ]);

            return ['success' => true, 'message' => 'Pago confirmado'];
        }

        $transaction->update([
            'status'             => 'failed',
            'transaction_status' => $transactionStatus,
            'kr_hash'            => $payload['kr-hash'] ?? null,
            'error_code'         => $transactionStatus,
            'error_message'      => 'Pago rechazado o expirado.',
            'izipay_response'    => $answer,
        ]);

        $transaction->order->update([
            'payment_status' => Order::PAYMENT_STATUS_FAILED,
        ]);

        return ['success' => false, 'message' => 'Pago rechazado'];
    }

    // ── Verificar firma kr-hash ─────────────────────────────────────────

    private function verifyKrHash(array $payload, string $rawKrAnswer = ''): bool
    {
        if ($this->mode === 'test' && empty($this->hashKey)) {
            Log::info('IzipayService: verificación de hash omitida (modo test sin hashKey)');
            return true;
        }

        $krHash = trim($payload['kr-hash'] ?? '');
        $krHashKeyType = trim($payload['kr-hash-key'] ?? '');
        $krAnswer = $rawKrAnswer ?: trim($payload['kr-answer'] ?? '');

        if (empty($krHash) || empty($krAnswer)) {
            Log::warning('IzipayService: kr-hash o kr-answer vacíos', [
                'has_hash'   => ! empty($krHash),
                'has_answer' => ! empty($krAnswer),
            ]);
            return false;
        }

        $keyToUse = $this->hashKey;

        if ($krHashKeyType === 'password') {
            $keyToUse = $this->password;
        } elseif ($krHashKeyType === 'sha256_hmac') {
            $keyToUse = $this->hashKey;
        }

        $expectedHash = hash_hmac('sha256', $krAnswer, $keyToUse);

        Log::info('IzipayService: verificando hash', [
            'received' => $krHash,
            'expected' => $expectedHash,
            'key_type' => $krHashKeyType,
            'match'    => hash_equals($expectedHash, $krHash),
        ]);

        return hash_equals($expectedHash, $krHash);
    }

    // ── Decodificar kr-answer ───────────────────────────────────────────

    private function decodeKrAnswer(string $krAnswer): ?array
    {
        if (empty($krAnswer)) {
            return null;
        }

        $decoded = json_decode($krAnswer, true);

        if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
            return $decoded;
        }

        $base64Decoded = base64_decode($krAnswer, true);
        if ($base64Decoded !== false) {
            $decoded = json_decode($base64Decoded, true);
            if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                return $decoded;
            }
        }

        Log::error('IzipayService: no se pudo decodificar kr-answer', [
            'first_chars' => substr($krAnswer, 0, 100),
        ]);

        return null;
    }
}
