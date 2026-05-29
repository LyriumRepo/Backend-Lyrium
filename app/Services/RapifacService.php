<?php

declare(strict_types=1);

namespace App\Services;

use App\Exceptions\RapifacException;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

final class RapifacService
{
    private const CACHE_KEY_TOKEN = 'rapifac_access_token';

    private const CACHE_TTL_SECONDS = 3300;

    private const DEFAULT_TIMEOUT = 30;

    private const DEFAULT_CONNECT_TIMEOUT = 10;

    private const DEFAULT_RETRY_ATTEMPTS = 3;

    public function __construct(
        private readonly string $authUrl,
        private readonly string $salesUrl,
        private readonly string $ruc,
        private readonly string $username,
        private readonly string $password,
        private readonly ?string $branchId = null,
        private readonly ?string $pdfBaseUrl = null,
        private readonly int $timeout = self::DEFAULT_TIMEOUT,
        private readonly int $connectTimeout = self::DEFAULT_CONNECT_TIMEOUT,
        private readonly int $retryAttempts = self::DEFAULT_RETRY_ATTEMPTS,
    ) {}

    public static function fromConfig(): self
    {
        return new self(
            authUrl: config('services.rapifac.auth_url'),
            salesUrl: config('services.rapifac.sales_url'),
            ruc: config('services.rapifac.ruc'),
            username: config('services.rapifac.user'),
            password: config('services.rapifac.password'),
            branchId: config('services.rapifac.branch_id'),
            pdfBaseUrl: config('services.rapifac.pdf_url'),
            timeout: (int) config('services.rapifac.timeout', self::DEFAULT_TIMEOUT),
            connectTimeout: (int) config('services.rapifac.connect_timeout', self::DEFAULT_CONNECT_TIMEOUT),
            retryAttempts: (int) config('services.rapifac.retry_attempts', self::DEFAULT_RETRY_ATTEMPTS),
        );
    }

    public function getToken(): string
    {
        return Cache::remember(self::CACHE_KEY_TOKEN, self::CACHE_TTL_SECONDS, function () {
            $this->logDebug('getToken — Solicitando token', [
                'auth_url' => $this->authUrl,
                'ruc' => substr($this->ruc, 0, 4) . '***' . substr($this->ruc, -4),
                'username' => $this->username,
                'has_password' => !empty($this->password),
            ]);

            try {
                $response = Http::asForm()
                    ->timeout($this->timeout)
                    ->connectTimeout($this->connectTimeout)
                    ->retry($this->retryAttempts, 200, throw: false)
                    ->post($this->authUrl, [
                        'grant_type' => 'password',
                        'client_id' => $this->ruc,
                        'username' => $this->username,
                        'password' => $this->password,
                    ]);
            } catch (ConnectionException $e) {
                $this->logError('getToken — Error de conexión', [
                    'error' => $e->getMessage(),
                    'auth_url' => $this->authUrl,
                ]);
                throw RapifacException::connectionError(
                    "No se pudo conectar con el servidor de autenticación de Rapifac ({$this->authUrl}). " .
                    'Verifica la URL en RAPIFAC_AUTH_URL y tu conexión a internet.',
                    ['auth_url' => $this->authUrl, 'error' => $e->getMessage()],
                );
            }

            $this->logDebug('getToken — Respuesta', [
                'status' => $response->status(),
                'body' => $this->sanitizeBody($response->body()),
            ]);

            if ($response->failed()) {
                $status = $response->status();
                $body = $response->body();

                $this->logError('getToken — Error HTTP', [
                    'status' => $status,
                    'body' => $this->sanitizeBody($body),
                ]);

                throw match ($status) {
                    400 => RapifacException::validationError(
                        "Solicitud inválida al obtener token. Verifica RAPIFAC_RUC, RAPIFAC_USER y RAPIFAC_PASSWORD en .env. " .
                        "Respuesta: {$body}",
                        ['status' => $status, 'body' => $body],
                    ),
                    401 => RapifacException::authError(
                        "Credenciales incorrectas. Verifica RAPIFAC_USER y RAPIFAC_PASSWORD en .env. " .
                        "Respuesta: {$body}",
                        ['status' => $status, 'body' => $body],
                    ),
                    default => RapifacException::serverError(
                        "El servidor de autenticación respondió con HTTP {$status}: {$body}",
                        ['status' => $status, 'body' => $body],
                    ),
                };
            }

            $data = $response->json();

            if (!isset($data['access_token'])) {
                $this->logError('getToken — No se obtuvo access_token', [
                    'response' => $data,
                    'status' => $response->status(),
                ]);
                throw RapifacException::authError(
                    'No se obtuvo access_token en la respuesta. ' .
                    'Verifica credenciales en .env (RAPIFAC_USER, RAPIFAC_PASSWORD, RAPIFAC_RUC).',
                    ['response' => $data],
                );
            }

            $this->logDebug('getToken — Token obtenido exitosamente');
            return $data['access_token'];
        });
    }

    public function emitInvoice(array $payload): array
    {
        $token = $this->getToken();

        $url = $this->branchId
            ? "{$this->salesUrl}?branchId={$this->branchId}"
            : $this->salesUrl;

        $this->logDebug('emitInvoice — Enviando comprobante', [
            'url' => $url,
            'payload' => $payload,
        ]);

        try {
            $response = Http::withToken($token)
                ->withHeaders(['Content-Type' => 'application/json'])
                ->timeout($this->timeout)
                ->connectTimeout($this->connectTimeout)
                ->retry($this->retryAttempts, 200, throw: false)
                ->post($url, $payload);
        } catch (ConnectionException $e) {
            $this->logError('emitInvoice — Error de conexión', [
                'url' => $url,
                'error' => $e->getMessage(),
            ]);
            throw RapifacException::connectionError(
                "No se pudo conectar con Rapifac para emitir el comprobante. Intenta nuevamente.",
                ['url' => $url, 'error' => $e->getMessage()],
            );
        }

        $this->logDebug('emitInvoice — Respuesta', [
            'status' => $response->status(),
            'body' => $this->sanitizeBody($response->body()),
        ]);

        if ($response->status() === 401) {
            $this->logDebug('emitInvoice — Token expirado, reintentando con token nuevo');
            $this->flushToken();
            $newToken = $this->getToken();

            try {
                $response = Http::withToken($newToken)
                    ->withHeaders(['Content-Type' => 'application/json'])
                    ->timeout($this->timeout)
                    ->connectTimeout($this->connectTimeout)
                    ->retry($this->retryAttempts, 200, throw: false)
                    ->post($url, $payload);
            } catch (ConnectionException $e) {
                throw RapifacException::connectionError(
                    "No se pudo conectar con Rapifac en el reintento.",
                    ['url' => $url, 'error' => $e->getMessage()],
                );
            }

            $this->logDebug('emitInvoice — Reintento respuesta', [
                'status' => $response->status(),
                'body' => $this->sanitizeBody($response->body()),
            ]);
        }

        if ($response->failed()) {
            $status = $response->status();
            $body = $response->body();

            $this->logError('emitInvoice — Error de API', [
                'status' => $status,
                'body' => $this->sanitizeBody($body),
                'url' => $url,
            ]);

            throw match ($status) {
                400 => RapifacException::validationError(
                    "Solicitud inválida a Rapifac: {$body}",
                    ['status' => $status, 'body' => $body, 'url' => $url],
                ),
                401 => RapifacException::authError(
                    "Token de acceso inválido incluso después de renovar. " .
                    "Verifica las credenciales en .env (RAPIFAC_USER, RAPIFAC_PASSWORD, RAPIFAC_RUC).",
                    ['status' => $status, 'body' => $body, 'url' => $url],
                ),
                422 => RapifacException::validationError(
                    "Datos del comprobante inválidos: {$body}",
                    ['status' => $status, 'body' => $body, 'url' => $url],
                ),
                default => RapifacException::serverError(
                    "Rapifac respondió con HTTP {$status}: {$body}",
                    ['status' => $status, 'body' => $body, 'url' => $url],
                ),
            };
        }

        return $response->json();
    }

    public function getInvoicePdfUrl(?string $providerInvoiceId): ?string
    {
        if (empty($providerInvoiceId)) {
            return null;
        }

        $base = $this->pdfBaseUrl ?? $this->salesUrl;

        return rtrim($base, '/') . "/invoices/{$providerInvoiceId}/pdf";
    }

    public function getInvoiceStatus(string $providerInvoiceId): ?array
    {
        try {
            $token = $this->getToken();
        } catch (RapifacException $e) {
            $this->logError('getInvoiceStatus — Error al obtener token', [
                'error' => $e->getMessage(),
                'provider_invoice_id' => $providerInvoiceId,
            ]);
            return null;
        }

        $url = rtrim($this->salesUrl, '/') . "/invoices/{$providerInvoiceId}";

        try {
            $response = Http::withToken($token)
                ->withHeaders(['Accept' => 'application/json'])
                ->timeout($this->timeout)
                ->connectTimeout($this->connectTimeout)
                ->retry($this->retryAttempts, 200, throw: false)
                ->get($url);

            if ($response->successful()) {
                return $response->json();
            }

            if ($response->status() === 401) {
                $this->flushToken();
            }

            $this->logWarning('getInvoiceStatus — No se pudo obtener estado', [
                'provider_invoice_id' => $providerInvoiceId,
                'status' => $response->status(),
                'body' => $this->sanitizeBody($response->body()),
            ]);

            return null;
        } catch (ConnectionException $e) {
            $this->logError('getInvoiceStatus — Error de conexión', [
                'provider_invoice_id' => $providerInvoiceId,
                'error' => $e->getMessage(),
            ]);
            return null;
        } catch (\Throwable $e) {
            $this->logError('getInvoiceStatus — Error', [
                'provider_invoice_id' => $providerInvoiceId,
                'error' => $e->getMessage(),
            ]);
            return null;
        }
    }

    public function queryInvoicesByCustomer(string $customerRuc): array
    {
        try {
            $token = $this->getToken();
        } catch (RapifacException $e) {
            $this->logError('queryInvoicesByCustomer — Error al obtener token', [
                'error' => $e->getMessage(),
                'customer_ruc' => $customerRuc,
            ]);
            return [];
        }

        $url = rtrim($this->salesUrl, '/') . '/invoices?customer_ruc=' . urlencode($customerRuc);

        if ($this->branchId) {
            $url .= '&branchId=' . urlencode($this->branchId);
        }

        try {
            $response = Http::withToken($token)
                ->withHeaders(['Accept' => 'application/json'])
                ->timeout($this->timeout)
                ->connectTimeout($this->connectTimeout)
                ->retry($this->retryAttempts, 200, throw: false)
                ->get($url);

            if ($response->successful()) {
                return $response->json()['data'] ?? [];
            }

            if ($response->status() === 401) {
                $this->flushToken();
            }

            $this->logWarning('queryInvoicesByCustomer — Error', [
                'customer_ruc' => $customerRuc,
                'status' => $response->status(),
                'body' => $this->sanitizeBody($response->body()),
            ]);

            return [];
        } catch (ConnectionException $e) {
            $this->logError('queryInvoicesByCustomer — Error de conexión', [
                'customer_ruc' => $customerRuc,
                'error' => $e->getMessage(),
            ]);
            return [];
        } catch (\Throwable $e) {
            $this->logError('queryInvoicesByCustomer — Exception', [
                'customer_ruc' => $customerRuc,
                'error' => $e->getMessage(),
            ]);
            return [];
        }
    }

    public function flushToken(): void
    {
        Cache::forget(self::CACHE_KEY_TOKEN);
    }

    private function sanitizeBody(string $body): string
    {
        if (mb_strlen($body) > 2000) {
            return mb_substr($body, 0, 2000) . '... [truncado]';
        }
        return $body;
    }

    private function logDebug(string $message, array $context = []): void
    {
        Log::debug("RapifacService::{$message}", $context);
    }

    private function logWarning(string $message, array $context = []): void
    {
        Log::warning("RapifacService::{$message}", $context);
    }

    private function logError(string $message, array $context = []): void
    {
        Log::error("RapifacService::{$message}", $context);
    }
}
