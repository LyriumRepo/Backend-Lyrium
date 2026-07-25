<?php

declare(strict_types=1);

namespace App\Services;

use App\Contracts\InvoiceProviderInterface;
use App\Exceptions\NubefactException;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

final class NubefactProvider implements InvoiceProviderInterface
{
    private const DEFAULT_TIMEOUT = 30;
    private const DEFAULT_CONNECT_TIMEOUT = 10;

    public function __construct(
        private readonly string $apiUrl,
        private readonly string $apiToken,
        private readonly int $timeout = self::DEFAULT_TIMEOUT,
        private readonly int $connectTimeout = self::DEFAULT_CONNECT_TIMEOUT,
    ) {}

    public static function fromConfig(): self
    {
        $apiUrl = config('services.nubefact.route')
            ?? throw NubefactException::configError(
                'NUBEFACT_ROUTE no está definido en .env. Sin esta clave el servicio no puede operar.'
            );

        $apiToken = config('services.nubefact.token')
            ?? throw NubefactException::configError(
                'NUBEFACT_TOKEN no está definido en .env.'
            );

        return new self(
            apiUrl: $apiUrl,
            apiToken: $apiToken,
            timeout: (int) config('services.nubefact.timeout', self::DEFAULT_TIMEOUT),
            connectTimeout: (int) config('services.nubefact.connect_timeout', self::DEFAULT_CONNECT_TIMEOUT),
        );
    }

    public function emitInvoice(array $payload): array
    {
        $url = rtrim($this->apiUrl, '/');

        $this->logDebug('emitInvoice — Enviando comprobante a NubeFact', [
            'url' => $url,
            'payload' => $payload,
        ]);

        try {
            $response = Http::withHeaders([
                    'Authorization' => 'Token token="' . $this->apiToken . '"',
                    'Content-Type' => 'application/json',
                ])
                ->timeout($this->timeout)
                ->connectTimeout($this->connectTimeout)
                ->post($url, $payload);
        } catch (ConnectionException $e) {
            $this->logError('emitInvoice — Error de conexión', [
                'url' => $url,
                'error' => $e->getMessage(),
            ]);
            throw NubefactException::connectionError(
                "No se pudo conectar con NubeFact para emitir el comprobante. Intenta nuevamente.",
                ['url' => $url, 'error' => $e->getMessage()],
            );
        }

        $this->logDebug('emitInvoice — Respuesta', [
            'status' => $response->status(),
            'body' => $this->sanitizeBody($response->body()),
        ]);

        if ($response->failed()) {
            $status = $response->status();
            $body = $response->body();
            $json = $response->json();

            $this->logError('emitInvoice — Error de API', [
                'status' => $status,
                'body' => $this->sanitizeBody($body),
                'url' => $url,
            ]);

            // NubeFact devuelve el detalle del error en distintas llaves según el endpoint;
            // 'errors' es la que usa /generar_comprobante (ej: {"errors":"...","codigo":21}).
            $errorMsg = $json['message'] ?? $json['error'] ?? $json['errors'] ?? $body;

            if ($status === 404) {
                $codigo = $json['codigo'] ?? null;
                throw NubefactException::configError(
                    "Ruta inválida (HTTP 404) — verifica NUBEFACT_ROUTE en .env" . ($codigo ? " (código Nubefact: {$codigo})" : '') . ". {$errorMsg}",
                    ['status' => $status, 'body' => $json, 'url' => $url],
                );
            }

            if ($status === 401) {
                throw NubefactException::authError(
                    "Token de acceso inválido. Verifica NUBEFACT_TOKEN en .env. Respuesta: {$errorMsg}",
                    ['status' => $status, 'body' => $errorMsg, 'url' => $url],
                );
            }

            if ($status === 400) {
                $codigo = $json['codigo'] ?? null;

                if ($codigo === 23) {
                    throw NubefactException::duplicateDocument(
                        "Este documento ya existe en NubeFact (código 23). {$errorMsg}",
                        ['status' => $status, 'body' => $json, 'url' => $url, 'codigo' => $codigo],
                    );
                }

                // El código 21 NO es exclusivo del límite de la cuenta DEMO — NubeFact lo
                // reutiliza también para RUC con dígito verificador incorrecto, series no
                // autorizadas, etc. Solo se clasifica como límite DEMO si el mensaje lo dice
                // explícitamente; cualquier otro caso cae a validationError (dato incorrecto,
                // no reintentable a ciegas — confirmado en producción: 2 de 3 fallas "código 21"
                // eran RUC inválido, no límite de cuenta).
                $isDemoLimit = $codigo === 21 && str_contains(mb_strtolower((string) $errorMsg), 'cuenta demo');

                if ($isDemoLimit) {
                    throw NubefactException::demoLimitExceeded(
                        "La cuenta NubeFact configurada es una cuenta DEMO y alcanzó su límite de 50 comprobantes (código 21). "
                        . "Elimina comprobantes de prueba en el panel de NubeFact o cambia a una cuenta de producción. {$errorMsg}",
                        ['status' => $status, 'body' => $json, 'url' => $url, 'codigo' => $codigo],
                    );
                }

                throw NubefactException::validationError(
                    "NubeFact — {$errorMsg}" . ($codigo ? " (código {$codigo})" : ''),
                    ['status' => $status, 'body' => $json, 'url' => $url, 'codigo' => $codigo],
                );
            }

            if ($status === 422) {
                $sunatObservation = $json['sunat_response']['observation'] ?? null;
                if ($sunatObservation) {
                    throw NubefactException::sunatObserved(
                        "{$errorMsg} — Observación SUNAT: {$sunatObservation}",
                        ['status' => $status, 'body' => $json, 'url' => $url],
                    );
                }

                throw NubefactException::validationError(
                    "Datos del comprobante inválidos: {$errorMsg}",
                    ['status' => $status, 'body' => $json, 'url' => $url],
                );
            }

            throw NubefactException::serverError(
                "NubeFact respondió con HTTP {$status}: {$errorMsg}",
                ['status' => $status, 'body' => $errorMsg, 'url' => $url],
            );
        }

        return $response->json();
    }

    public function getInvoiceStatus(string $providerInvoiceId): ?array
    {
        $url = rtrim($this->apiUrl, '/') . "?operacion=consultar_comprobante&id={$providerInvoiceId}";

        try {
            $response = Http::withHeaders([
                    'Authorization' => 'Token token="' . $this->apiToken . '"',
                    'Accept' => 'application/json',
                ])
                ->timeout($this->timeout)
                ->connectTimeout($this->connectTimeout)
                ->get($url);

            if ($response->successful()) {
                return $response->json();
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

    public function getInvoicePdfUrl(?string $providerInvoiceId): ?string
    {
        if (empty($providerInvoiceId)) {
            return null;
        }

        return rtrim($this->apiUrl, '/') . "?operacion=obtener_pdf&id={$providerInvoiceId}";
    }

    public function getInvoiceXmlUrl(?string $providerInvoiceId): ?string
    {
        if (empty($providerInvoiceId)) {
            return null;
        }

        return rtrim($this->apiUrl, '/') . "?operacion=obtener_xml&id={$providerInvoiceId}";
    }

    public function getCdrUrl(?string $providerInvoiceId): ?string
    {
        if (empty($providerInvoiceId)) {
            return null;
        }

        return rtrim($this->apiUrl, '/') . "?operacion=obtener_cdr&id={$providerInvoiceId}";
    }

    public function getAvailableSeries(): array
    {
        $series = config('services.nubefact.series', []);

        return collect($series)->map(fn (string $serie, string $type) => [
            'type' => $type,
            'series' => $serie,
            'label' => match ($type) {
                'FACTURA' => "Factura ({$serie})",
                'BOLETA' => "Boleta ({$serie})",
                'NOTA_CREDITO' => "Nota Crédito ({$serie})",
                default => "{$type} ({$serie})",
            },
        ])->values()->all();
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
        Log::debug("NubefactProvider::{$message}", $context);
    }

    private function logWarning(string $message, array $context = []): void
    {
        Log::warning("NubefactProvider::{$message}", $context);
    }

    private function logError(string $message, array $context = []): void
    {
        Log::error("NubefactProvider::{$message}", $context);
    }
}
