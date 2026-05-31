<?php

declare(strict_types=1);

namespace App\Services;

use App\Events\NewBookingReceived;
use App\Models\IzipayBookingTransaction;
use App\Models\Service;
use App\Models\ServiceBooking;
use App\Notifications\BookingCreatedNotification;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

final class IzipayBookingService
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

    public function createSession(int $userId, int $serviceId, string $email, array $bookingData = []): IzipayBookingTransaction
    {
        $service = Service::findOrFail($serviceId);

        $amountCents = IzipayBookingTransaction::toCents($service->price);
        $izipayOrderId = IzipayBookingTransaction::generateIzipayOrderId($service->id);

        $appointmentDate = null;
        if (! empty($bookingData['appointment_date']) && ! empty($bookingData['start_time'])) {
            $appointmentDate = \Carbon\Carbon::parse($bookingData['appointment_date'].' '.$bookingData['start_time']);
        }

        $transaction = IzipayBookingTransaction::create([
            'user_id' => $userId,
            'service_id' => $service->id,
            'schedule_id' => $bookingData['schedule_id'] ?? null,
            'specialist_id' => $bookingData['specialist_id'] ?? null,
            'appointment_date' => $appointmentDate,
            'customer_notes' => $bookingData['customer_notes'] ?? null,
            'amount_in_cents' => $amountCents,
            'currency' => 'PEN',
            'izipay_order_id' => $izipayOrderId,
            'status' => 'pending',
            'mode' => $this->mode,
        ]);

        $credentials = base64_encode("{$this->userId}:{$this->password}");

        $payload = [
            'amount' => $amountCents,
            'currency' => 'PEN',
            'orderId' => $izipayOrderId,
            'customer' => ['email' => $email],
            'metadata' => [
                'type' => 'booking',
                'transaction_id' => (string) $transaction->id,
                'service_id' => (string) $service->id,
            ],
        ];

        Log::info('IzipayBookingService: creando sesión', [
            'transaction_id' => $transaction->id,
            'amount_cents' => $amountCents,
        ]);

        try {
            $response = Http::withHeaders([
                'Authorization' => 'Basic '.$credentials,
                'Content-Type' => 'application/json',
            ])
                ->timeout(30)
                ->post(self::API_URL, $payload);

            $data = $response->json();

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

            throw new \RuntimeException('No se pudo conectar con el servidor de pagos.');
        }
    }

    public function processWebhook(array $payload, string $rawKrAnswer): array
    {
        if (! $this->verifyKrHash($payload, $rawKrAnswer)) {
            Log::warning('IzipayBookingService: firma inválida');

            return ['success' => false, 'message' => 'Firma inválida'];
        }

        $answer = $this->decodeKrAnswer($rawKrAnswer ?: ($payload['kr-answer'] ?? ''));

        if (! $answer) {
            return ['success' => false, 'message' => 'No se pudo decodificar kr-answer'];
        }

        $izipayOrderId = $answer['orderDetails']['orderId']
            ?? $answer['orderId']
            ?? null;

        if (! $izipayOrderId) {
            return ['success' => false, 'message' => 'orderId no encontrado'];
        }

        $transaction = IzipayBookingTransaction::where('izipay_order_id', $izipayOrderId)->first();

        if (! $transaction) {
            $txId = $answer['transactions'][0]['metadata']['transaction_id']
                ?? $answer['metadata']['transaction_id']
                ?? null;

            if ($txId) {
                $transaction = IzipayBookingTransaction::find($txId);
            }
        }

        if (! $transaction) {
            return ['success' => false, 'message' => 'Transacción no encontrada'];
        }

        if ($transaction->isPaid()) {
            return ['success' => true, 'message' => 'Ya procesado'];
        }

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

            $booking = $this->createBookingFromTransaction($transaction);

            if ($booking) {
                $transaction->update(['booking_id' => $booking->id]);
            }

            return ['success' => true, 'message' => 'Pago confirmado', 'booking_id' => $booking?->id];
        }

        $transaction->update([
            'status' => 'failed',
            'transaction_status' => $transactionStatus,
            'kr_hash' => $payload['kr-hash'] ?? null,
            'error_code' => $transactionStatus,
            'error_message' => 'Pago rechazado o expirado.',
            'izipay_response' => $answer,
        ]);

        return ['success' => false, 'message' => 'Pago rechazado'];
    }

    private function createBookingFromTransaction(IzipayBookingTransaction $tx): ?ServiceBooking
    {
        try {
            $booking = DB::transaction(function () use ($tx) {
                $schedule = \App\Models\ServiceSchedule::lockForUpdate()->findOrFail($tx->schedule_id);

                if (! $schedule->isAvailableForBooking($tx->appointment_date->toDateTimeString())) {
                    throw new \InvalidArgumentException('El horario ya no está disponible');
                }

                return ServiceBooking::create([
                    'service_id' => $tx->service_id,
                    'user_id' => $tx->user_id,
                    'schedule_id' => $tx->schedule_id,
                    'specialist_id' => $tx->specialist_id,
                    'appointment_date' => $tx->appointment_date,
                    'status' => ServiceBooking::STATUS_PENDING,
                    'total_price' => $tx->service->price,
                    'payment_method' => 'izipay_'.strtolower($tx->payment_method_type ?? 'card'),
                    'payment_status' => 'paid',
                    'customer_notes' => $tx->customer_notes,
                ]);
            });

            $booking->load(['service.store', 'user', 'specialist']);

            $eventIds = $this->googleCalendar->createEvent($booking);

            $updateData = array_filter([
                'google_event_id' => $eventIds['specialist'],
                'google_event_id_client' => $eventIds['client'],
                'google_event_id_seller' => $eventIds['seller'],
            ]);

            if (! empty($updateData)) {
                $booking->update($updateData);
            }

            broadcast(new NewBookingReceived($booking));

            $storeUser = $booking->service?->store?->owner;
            if ($storeUser) {
                $storeUser->notify(new BookingCreatedNotification($booking, 'seller'));
            }

            return $booking;
        } catch (\Throwable $e) {
            Log::error('IzipayBookingService: error creando booking desde transacción', [
                'transaction_id' => $tx->id,
                'error' => $e->getMessage(),
            ]);

            return null;
        }
    }

    public function getStatus(int $transactionId): ?IzipayBookingTransaction
    {
        return IzipayBookingTransaction::with(['service', 'booking'])->find($transactionId);
    }

    private function verifyKrHash(array $payload, string $rawKrAnswer): bool
    {
        if ($this->mode === 'test' && empty($this->hashKey)) {
            return true;
        }

        $krHash = trim($payload['kr-hash'] ?? '');
        $krHashKeyType = trim($payload['kr-hash-key'] ?? '');
        $krAnswer = $rawKrAnswer ?: trim($payload['kr-answer'] ?? '');

        if (empty($krHash) || empty($krAnswer)) {
            return false;
        }

        $keyToUse = $this->hashKey;

        if ($krHashKeyType === 'password') {
            $keyToUse = $this->password;
        } elseif ($krHashKeyType === 'sha256_hmac') {
            $keyToUse = $this->hashKey;
        }

        $expectedHash = hash_hmac('sha256', $krAnswer, $keyToUse);

        return hash_equals($expectedHash, $krHash);
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

        $base64Decoded = base64_decode($krAnswer, true);

        if ($base64Decoded !== false) {
            $decoded = json_decode($base64Decoded, true);

            if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                return $decoded;
            }
        }

        return null;
    }
}
