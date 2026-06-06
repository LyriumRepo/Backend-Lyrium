<?php

declare(strict_types=1);

namespace App\Services;

/**
 * ARCHIVO: app/Services/IzipayService.php
 *
 * Encapsula toda comunicación con la API de Izipay (Lyra/Micuentaweb).
 *
 * CORRECCIÓN PRINCIPAL:
 * El webhook de Izipay llega como application/x-www-form-urlencoded.
 * Laravel decodifica los valores al hacer $request->all(), lo que puede
 * alterar el string de kr-answer y romper la verificación del hash.
 * La solución: pasar el kr-answer RAW (sin tocar) al hash_hmac.
 * Para eso, el Controller ahora extrae el raw body y lo pasa al servicio.
 */

use App\Events\NewBookingReceived;
use App\Mail\OrderConfirmationMail;
use App\Models\IzipayOrderTransaction;
use App\Models\Order;
use App\Models\ServiceBooking;
use App\Models\ServiceSlotHold;
use App\Notifications\BookingCreatedNotification;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

final class IzipayService
{
    private const API_URL = 'https://api.micuentaweb.pe/api-payment/V4/Charge/CreatePayment';

    private string $userId;

    private string $password;

    private string $mode;

    private string $hashKey;

    public function __construct(
        private readonly GoogleCalendarService $googleCalendar,
    ) {
        $this->userId = (string) config('services.izipay.user_id', '');
        $this->password = (string) config('services.izipay.password', '');
        $this->mode = (string) config('services.izipay.mode', 'test');
        $this->hashKey = trim((string) config('services.izipay.hash_key', ''));
    }

    // ── Crear sesión de pago (formToken) ──────────────────────────────────

    public function createPaymentSession(Order $order, string $email, string $cartToken = ''): IzipayOrderTransaction
    {
        // Service prices are already included in order->total (see OrderController::store)
        $holdIds = [];

        if ($cartToken) {
            $holds = ServiceSlotHold::active()->forCart($cartToken)->with('service')->get();
            $holdIds = $holds->pluck('id')->toArray();
        }

        $amountCents = IzipayOrderTransaction::toCents((float) $order->total);
        $izipayOrderId = IzipayOrderTransaction::generateIzipayOrderId($order->id);

        $transaction = IzipayOrderTransaction::create([
            'order_id' => $order->id,
            'user_id' => $order->user_id,
            'izipay_order_id' => $izipayOrderId,
            'status' => 'pending',
            'amount_in_cents' => $amountCents,
            'currency' => 'PEN',
            'mode' => $this->mode,
        ]);

        $credentials = base64_encode("{$this->userId}:{$this->password}");

        $metadata = [
            'order_id' => (string) $order->id,
            'order_number' => $order->order_number,
        ];

        // Embed active service holds in metadata for webhook processing
        if (! empty($holdIds)) {
            $metadata['holds'] = json_encode(array_map(function ($id) {
                return (string) $id;
            }, $holdIds));

            // Include service addresses for webhook processing
            $addresses = [];
            foreach ($holds as $hold) {
                if ($hold->service_address) {
                    $addresses[(string) $hold->id] = $hold->service_address;
                }
            }
            if (! empty($addresses)) {
                $metadata['hold_addresses'] = json_encode($addresses);
            }
        }

        $payload = [
            'amount' => $amountCents,
            'currency' => 'PEN',
            'orderId' => $izipayOrderId,
            'customer' => ['email' => $email],
            'metadata' => $metadata,
        ];

        Log::info('IzipayService: creando sesión de pago', [
            'order_id' => $order->id,
            'izipay_order_id' => $izipayOrderId,
            'amount_cents' => $amountCents,
        ]);

        try {
            $response = Http::withHeaders([
                'Authorization' => "Basic {$credentials}",
                'Content-Type' => 'application/json',
            ])
                ->timeout(30)
                ->post(self::API_URL, $payload);

            $data = $response->json();

            Log::info('IzipayService: respuesta recibida', [
                'status' => $data['status'] ?? null,
                'order_id' => $order->id,
            ]);

            if (($data['status'] ?? '') === 'SUCCESS' && isset($data['answer']['formToken'])) {
                $transaction->update([
                    'form_token' => $data['answer']['formToken'],
                    'izipay_response' => $data,
                ]);

                return $transaction;
            }

            $errorMsg = $data['answer']['errorMessage']
                ?? $data['answer']['detailedErrorMessage']
                ?? 'Error al crear la sesión de pago con Izipay.';

            $transaction->update([
                'status' => 'failed',
                'error_code' => (string) ($data['answer']['errorCode'] ?? 'IZIPAY_ERROR'),
                'error_message' => $errorMsg,
                'izipay_response' => $data,
            ]);

            throw new \RuntimeException($errorMsg);
        } catch (\Illuminate\Http\Client\ConnectionException $e) {
            $transaction->update([
                'status' => 'failed',
                'error_code' => 'CONNECTION_ERROR',
                'error_message' => 'No se pudo conectar con Izipay.',
            ]);

            Log::error('IzipayService: error de conexión', [
                'order_id' => $order->id,
                'error' => $e->getMessage(),
            ]);

            throw new \RuntimeException('No se pudo conectar con el servidor de pagos. Intenta nuevamente.');
        }
    }

    // ── Procesar webhook de Izipay ────────────────────────────────────────

    /**
     * Procesa la notificación POST que Izipay envía después del pago.
     *
     * IMPORTANTE: $rawKrAnswer debe ser el string CRUDO de kr-answer,
     * tal como llegó en el body — sin pasar por json_decode/json_encode.
     * Cualquier modificación al string rompe la verificación del hash.
     *
     * @param  array  $payload  Datos POST parseados ($request->all())
     * @param  string  $rawKrAnswer  String crudo de kr-answer ($request->input('kr-answer'))
     */
    public function processWebhook(array $payload, string $rawKrAnswer = ''): array
    {
        // 1. Verificar firma usando el string RAW de kr-answer
        if (! $this->verifyKrHash($payload, $rawKrAnswer)) {
            Log::warning('IzipayService webhook: firma inválida', [
                'kr_hash' => $payload['kr-hash'] ?? null,
                'hash_key_length' => strlen($this->hashKey),
                'answer_length' => strlen($rawKrAnswer),
            ]);

            return ['success' => false, 'message' => 'Firma inválida'];
        }

        // 2. Decodificar kr-answer
        $answer = $this->decodeKrAnswer($rawKrAnswer ?: ($payload['kr-answer'] ?? ''));

        if (! $answer) {
            return ['success' => false, 'message' => 'No se pudo decodificar kr-answer'];
        }

        $orderInfo = $answer['orderDetails'] ?? [];
        $izipayOrderId = $orderInfo['orderId'] ?? $answer['orderId'] ?? null;

        Log::info('IzipayService webhook recibido', [
            'izipay_order_id' => $izipayOrderId,
            'transaction_status' => $answer['orderStatus'] ?? null,
        ]);

        if (! $izipayOrderId) {
            return ['success' => false, 'message' => 'orderId no encontrado en webhook'];
        }

        // 3. Buscar transacción local
        $transaction = IzipayOrderTransaction::where('izipay_order_id', $izipayOrderId)
            ->latest()
            ->first();

        // Fallback: buscar por metadata si no encontró por izipay_order_id
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

        // Evitar doble procesamiento
        if ($transaction->isPaid()) {
            return ['success' => true, 'message' => 'Ya procesado'];
        }

        // 4. Determinar estado del pago
        $txData = $answer['transactions'][0] ?? [];
        $transactionStatus = $answer['orderStatus']
            ?? $txData['detailedStatus']
            ?? $txData['status']
            ?? '';

        $isPaid = in_array($transactionStatus, ['PAID', 'AUTHORISED', 'CAPTURED', 'ACCEPTED'], true);

        if ($isPaid) {
            $pan = $txData['transactionDetails']['cardDetails']['pan'] ?? null;
            $cardLast4 = $pan ? substr($pan, -4) : null;
            $transaction->update([
                'status' => 'paid',
                'transaction_status' => $transactionStatus,
                'transaction_uuid' => $txData['uuid'] ?? null,
                'payment_method_type' => $txData['paymentMethodType'] ?? null,
                'card_brand' => $txData['transactionDetails']['cardDetails']['effectiveBrand'] ?? null,
                'card_last4' => $cardLast4,
                'kr_hash' => $payload['kr-hash'] ?? null,
                'izipay_response' => $answer,
            ]);

            $order = $transaction->order;
            $order->update([
                'payment_status' => Order::PAYMENT_STATUS_PAID,
                'payment_method' => 'izipay_'.strtolower($txData['paymentMethodType'] ?? 'card'),
            ]);

            Log::info('IzipayService webhook: pago confirmado ✓', [
                'order_id' => $order->id,
                'tx_status' => $transactionStatus,
                'amount' => $txData['amount'] ?? null,
            ]);

            // ── Accrue Lirios points ──────────────────────────────────────────
            try {
                $liriosService = app(\App\Services\LiriosService::class);
                $liriosService->accrue($order->user_id, (float) $order->total, $order);
                Log::info('IzipayService: Lirios acumulados', [
                    'order_id' => $order->id,
                    'total' => $order->total,
                ]);
            } catch (\Throwable $liriosEx) {
                Log::error('IzipayService: error acumulando Lirios', [
                    'order_id' => $order->id,
                    'error' => $liriosEx->getMessage(),
                ]);
            }

            // ── Send order confirmation email (only if there are product items) ──
            if ($order->items()->exists()) {
                try {
                    Mail::to($order->user?->email)->send(new OrderConfirmationMail($order));
                } catch (\Throwable $mailEx) {
                    Log::warning('Error enviando correo de confirmación de orden', [
                        'order_id' => $order->id,
                        'error' => $mailEx->getMessage(),
                        'trace' => $mailEx->getTraceAsString(),
                    ]);
                }
            }

            // ── Create ServiceBookings from holds ──────────────────────────
            $holdIds = $this->extractHoldIdsFromMetadata($answer);
            if (! empty($holdIds)) {
                $holdAddresses = $this->extractHoldAddressesFromMetadata($answer);
                $this->createBookingsFromHolds($holdIds, $holdAddresses, $order);
            }

            return ['success' => true, 'message' => 'Pago confirmado'];
        }

        // Pago rechazado o expirado
        $transaction->update([
            'status' => 'failed',
            'transaction_status' => $transactionStatus,
            'kr_hash' => $payload['kr-hash'] ?? null,
            'error_code' => $transactionStatus,
            'error_message' => 'Pago rechazado o expirado.',
            'izipay_response' => $answer,
        ]);

        $transaction->order->update([
            'payment_status' => Order::PAYMENT_STATUS_FAILED,
        ]);

        return ['success' => false, 'message' => 'Pago rechazado'];
    }

    // ── Verificar firma kr-hash ───────────────────────────────────────────

    /**
     * CORRECCIÓN CLAVE:
     * Evalúa el tipo de llave (password o hash_key) que Izipay usó para
     * firmar la petición, leyendo el parámetro 'kr-hash-key' del payload.
     */
    private function verifyKrHash(array $payload, string $rawKrAnswer = ''): bool
    {
        // En modo test sin hashKey → aceptar siempre (útil para desarrollo rápido)
        if ($this->mode === 'test' && empty($this->hashKey)) {
            Log::info('IzipayService: verificación de hash omitida (modo test sin hashKey)');

            return true;
        }

        $krHash = trim($payload['kr-hash'] ?? '');
        $krHashKeyType = trim($payload['kr-hash-key'] ?? ''); // Extraemos el tipo de llave usada

        // Usar el raw answer si está disponible, sino caer en el del payload
        $krAnswer = $rawKrAnswer ?: trim($payload['kr-answer'] ?? '');

        if (empty($krHash) || empty($krAnswer)) {
            Log::warning('IzipayService: kr-hash o kr-answer vacíos', [
                'has_hash' => ! empty($krHash),
                'has_answer' => ! empty($krAnswer),
            ]);

            return false;
        }

        // ---------------------------------------------------------
        // NUEVA LÓGICA: Determinar qué llave usar
        // ---------------------------------------------------------
        $keyToUse = $this->hashKey; // Valor por defecto

        if ($krHashKeyType === 'password') {
            $keyToUse = $this->password;
        } elseif ($krHashKeyType === 'sha256_hmac') {
            $keyToUse = $this->hashKey;
        }

        // Calculamos el hash esperado con la llave correcta
        $expectedHash = hash_hmac('sha256', $krAnswer, $keyToUse);

        Log::info('IzipayService: verificando hash', [
            'received' => $krHash,
            'expected' => $expectedHash,
            'key_type' => $krHashKeyType, // Añadido para debugging
            'match' => hash_equals($expectedHash, $krHash),
        ]);

        return hash_equals($expectedHash, $krHash);
    }

    // ── Decodificar kr-answer ─────────────────────────────────────────────

    private function decodeKrAnswer(string $krAnswer): ?array
    {
        if (empty($krAnswer)) {
            return null;
        }

        // kr-answer viene como JSON string directo
        $decoded = json_decode($krAnswer, true);

        if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
            return $decoded;
        }

        // Fallback: intentar base64
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

    // ── Helpers for unified checkout (service holds) ─────────────────────

    /**
     * Extract hold IDs from Izipay metadata.
     */
    private function extractHoldIdsFromMetadata(array $answer): array
    {
        $metadata = $answer['transactions'][0]['metadata']
            ?? $answer['metadata']
            ?? [];

        $holdsRaw = $metadata['holds'] ?? '';
        if (empty($holdsRaw)) {
            return [];
        }

        $ids = json_decode($holdsRaw, true);
        if (! is_array($ids) || empty($ids)) {
            Log::warning('IzipayService: holds metadata malformed', ['raw' => $holdsRaw]);

            return [];
        }

        return array_filter(array_map('intval', $ids));
    }

    /**
     * Extract hold addresses from Izipay metadata.
     */
    private function extractHoldAddressesFromMetadata(array $answer): array
    {
        $metadata = $answer['transactions'][0]['metadata']
            ?? $answer['metadata']
            ?? [];

        $raw = $metadata['hold_addresses'] ?? '';
        if (empty($raw)) {
            return [];
        }

        $addresses = json_decode($raw, true);
        if (! is_array($addresses)) {
            return [];
        }

        return $addresses;
    }

    /**
     * Create ServiceBooking records from paid holds,
     * sync with Google Calendar, send notifications and emails.
     */
    private function createBookingsFromHolds(array $holdIds, array $holdAddresses, Order $order): void
    {
        $holds = ServiceSlotHold::whereIn('id', $holdIds)
            ->active()
            ->where('cart_token', 'LIKE', '%') // any cart_token
            ->get();

        if ($holds->isEmpty()) {
            Log::info('IzipayService: no active holds found for IDs', ['ids' => $holdIds]);

            return;
        }

        $bookings = [];

        DB::transaction(function () use ($holds, $holdAddresses, $order, &$bookings) {
            foreach ($holds as $hold) {
                $service = $hold->service;

                if (! $service) {
                    Log::warning('IzipayService: hold references missing service', ['hold_id' => $hold->id]);

                    continue;
                }

                $appointmentDate = \Carbon\Carbon::parse(
                    $hold->appointment_date->format('Y-m-d').' '.$hold->start_time
                );

                $address = $holdAddresses[(string) $hold->id] ?? $hold->service_address;

                $booking = ServiceBooking::create([
                    'service_id' => $service->id,
                    'user_id' => $order->user_id,
                    'schedule_id' => $hold->schedule_id,
                    'specialist_id' => $hold->specialist_id,
                    'appointment_date' => $appointmentDate,
                    'status' => ServiceBooking::STATUS_CONFIRMED,
                    'total_price' => $service->finalPrice(),
                    'payment_method' => 'izipay_'.($order->payment_method ?? 'card'),
                    'payment_status' => 'paid',
                    'customer_notes' => $hold->customer_notes,
                    'service_address' => $address,
                    'confirmed_at' => now(),
                ]);

                $bookings[] = $booking;
                $hold->delete();
            }
        });

        // Post-creation: Google Calendar + notifications + broadcast (outside transaction)
        foreach ($bookings as $booking) {
            $booking->loadMissing(['service.store', 'user', 'specialist']);

            // Google Calendar event + confirmation emails
            $eventIds = $this->googleCalendar->createEvent($booking);

            $updateData = array_filter([
                'google_event_id' => $eventIds['specialist'],
                'google_event_id_client' => $eventIds['client'],
                'google_event_id_seller' => $eventIds['seller'],
            ]);

            if (! empty($updateData)) {
                $booking->update($updateData);
            }

            // WebSocket broadcast
            broadcast(new NewBookingReceived($booking));

            // Database notification to store owner
            $storeUser = $booking->service?->store?->owner;
            if ($storeUser) {
                $storeUser->notify(new BookingCreatedNotification($booking, 'seller'));
            }
        }

        $count = count($bookings);
        Log::info("IzipayService: {$count} ServiceBooking(s) creados desde holds con Google Calendar", [
            'order_id' => $order->id,
            'hold_ids' => $holdIds,
        ]);
    }
}
