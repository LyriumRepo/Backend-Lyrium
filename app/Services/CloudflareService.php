<?php

declare(strict_types=1);

namespace App\Services;

use App\DTO\Cloudflare\AnalyticsDTO;
use App\DTO\Cloudflare\DNSRecordDTO;
use App\DTO\Cloudflare\FirewallEventDTO;
use App\DTO\Cloudflare\SecurityDTO;
use App\DTO\Cloudflare\ZoneDTO;
use App\Exceptions\Cloudflare\CloudflareAuthenticationException;
use App\Exceptions\Cloudflare\CloudflareConnectionException;
use App\Exceptions\Cloudflare\CloudflareNotFoundException;
use App\Exceptions\Cloudflare\CloudflareRateLimitException;
use App\Exceptions\Cloudflare\CloudflareValidationException;
use App\Support\Cloudflare\CloudflareEndpointBuilder;
use App\Support\Cloudflare\CloudflareResponseMapper;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Servicio de integración con la API oficial de Cloudflare v4.
 *
 * Responsabilidad: toda la comunicación con Cloudflare vive aquí.
 * No contiene lógica de negocio del dominio.
 *
 * @see https://developers.cloudflare.com/api/
 */
final class CloudflareService
{
    private readonly PendingRequest $http;

    private readonly CloudflareEndpointBuilder $endpoints;

    private readonly CloudflareResponseMapper $mapper;

    public function __construct()
    {
        $this->endpoints = new CloudflareEndpointBuilder;
        $this->mapper = new CloudflareResponseMapper;

        $token = config('cloudflare.api_token');

        if (! is_string($token) || $token === '') {
            throw new CloudflareAuthenticationException(
                'API Token de Cloudflare no configurado. Verifique CLOUDFLARE_API_TOKEN en .env.'
            );
        }

        $this->http = Http::withToken($token)
            ->withHeaders([
                'Content-Type' => 'application/json',
                'Accept' => 'application/json',
            ])
            ->timeout((int) config('cloudflare.timeout', 15))
            ->retry(
                (int) config('cloudflare.retry_times', 3),
                (int) config('cloudflare.retry_sleep_ms', 500),
                function (\Exception $e): bool {
                    return ! $e instanceof CloudflareAuthenticationException;
                }
            )
            ->withOptions([
                'verify' => (bool) config('cloudflare.verify_ssl', true),
            ]);
    }

    // ================================================================
    // ZONE
    // ================================================================

    /**
     * Obtiene información de la zone configurada.
     *
     * @throws CloudflareAuthenticationException
     * @throws CloudflareConnectionException
     * @throws CloudflareNotFoundException
     */
    public function getZone(): ZoneDTO
    {
        $response = $this->get($this->endpoints->zone());

        $data = $this->mapper->extractSingleResult($response);

        return ZoneDTO::fromApiResponse($data);
    }

    // ================================================================
    // ANALYTICS
    // ================================================================

    /**
     * Obtiene analíticas del dashboard (requests, bandwidth, threats).
     *
     * @param  string  $since  ISO8601 (ej: "2026-06-01T00:00:00Z")
     * @param  string  $until  ISO8601
     */
    public function getAnalytics(string $since = '', string $until = ''): AnalyticsDTO
    {
        try {
            $response = $this->get($this->endpoints->analytics($since, $until));

            $data = $this->mapper->extractSingleResult($response);

            return AnalyticsDTO::fromApiResponse($data);
        } catch (\Throwable $e) {
            Log::warning('Cloudflare analytics no disponible', ['message' => $e->getMessage()]);

            return AnalyticsDTO::fromApiResponse([]);
        }
    }

    // ================================================================
    // SECURITY ANALYTICS
    // ================================================================

    /**
     * Obtiene analíticas de seguridad (top paths, top countries, etc.).
     *
     * @param  string  $since  ISO8601
     * @param  string  $until  ISO8601
     */
    public function getSecurityAnalytics(string $since = '', string $until = ''): SecurityDTO
    {
        try {
            $response = $this->get($this->endpoints->securityAnalytics($since, $until));

            $data = $this->mapper->extractSingleResult($response);

            $events = array_map(
                fn (array $event) => FirewallEventDTO::fromApiResponse($event),
                $data['security_events'] ?? []
            );

            return SecurityDTO::fromApiResponse($data, $events);
        } catch (\Throwable $e) {
            Log::warning('Cloudflare security analytics no disponible', ['message' => $e->getMessage()]);

            return SecurityDTO::fromApiResponse([], []);
        }
    }

    // ================================================================
    // FIREWALL EVENTS
    // ================================================================

    /**
     * Obtiene eventos de firewall (WAF, rate limit, IP blocks, challenges).
     *
     * @return FirewallEventDTO[]
     */
    public function getFirewallEvents(int $perPage = 50, int $page = 1): array
    {
        try {
            $response = $this->get($this->endpoints->firewallEvents($perPage, $page));

            $results = $this->mapper->extractResult($response);

            return array_map(
                fn (array $event) => FirewallEventDTO::fromApiResponse($event),
                $results
            );
        } catch (\Throwable $e) {
            Log::warning('Cloudflare firewall events no disponible', ['message' => $e->getMessage()]);

            return [];
        }
    }

    // ================================================================
    // DNS RECORDS
    // ================================================================

    /**
     * Obtiene todos los registros DNS.
     *
     * @return DNSRecordDTO[]
     */
    public function getDnsRecords(string $search = ''): array
    {
        $response = $this->get($this->endpoints->dnsRecords($search));

        $results = $this->mapper->extractResult($response);

        return array_map(
            fn (array $record) => DNSRecordDTO::fromApiResponse($record),
            $results
        );
    }

    /**
     * Obtiene un registro DNS específico.
     */
    public function getDnsRecord(string $recordId): DNSRecordDTO
    {
        $response = $this->get($this->endpoints->dnsRecord($recordId));

        $data = $this->mapper->extractSingleResult($response);

        return DNSRecordDTO::fromApiResponse($data);
    }

    /**
     * Crea un registro DNS.
     *
     * @param  array  $data  {type, name, content, ttl, proxied}
     *
     * @throws CloudflareValidationException
     */
    public function createDnsRecord(array $data): DNSRecordDTO
    {
        $response = $this->post($this->endpoints->dnsRecords(), $data);

        $result = $this->mapper->extractSingleResult($response);

        return DNSRecordDTO::fromApiResponse($result);
    }

    /**
     * Actualiza un registro DNS.
     *
     * @throws CloudflareValidationException
     */
    public function updateDnsRecord(string $recordId, array $data): DNSRecordDTO
    {
        $response = $this->patch($this->endpoints->dnsRecord($recordId), $data);

        $result = $this->mapper->extractSingleResult($response);

        return DNSRecordDTO::fromApiResponse($result);
    }

    /**
     * Elimina un registro DNS.
     */
    public function deleteDnsRecord(string $recordId): bool
    {
        $response = $this->delete($this->endpoints->dnsRecord($recordId));

        $body = $this->mapper->validateAndExtract($response);

        return (bool) ($body['success'] ?? false);
    }

    // ================================================================
    // CACHE
    // ================================================================

    /**
     * Purga todo el caché de la zone.
     */
    public function purgeCache(): bool
    {
        $response = $this->post($this->endpoints->purgeCache(), [
            'purge_everything' => true,
        ]);

        $body = $this->mapper->validateAndExtract($response);

        return (bool) ($body['success'] ?? false);
    }

    /**
     * Purga archivos específicos del caché.
     *
     * @param  string[]  $files  URLs completas a purgar
     */
    public function purgeCacheFiles(array $files): bool
    {
        $response = $this->post($this->endpoints->purgeCache(), [
            'files' => $files,
        ]);

        $body = $this->mapper->validateAndExtract($response);

        return (bool) ($body['success'] ?? false);
    }

    /**
     * Purga tags específicos del caché (requiere Cache Reserve habilitado).
     *
     * @param  string[]  $tags
     */
    public function purgeCacheByTags(array $tags): bool
    {
        $response = $this->post($this->endpoints->purgeCache(), [
            'tags' => $tags,
        ]);

        $body = $this->mapper->validateAndExtract($response);

        return (bool) ($body['success'] ?? false);
    }

    // ================================================================
    // WAF
    // ================================================================

    /**
     * Obtiene los paquetes WAF disponibles.
     */
    public function getWafPackages(): array
    {
        $response = $this->get($this->endpoints->wafPackages());

        return $this->mapper->extractResult($response);
    }

    /**
     * Obtiene reglas de un paquete WAF.
     */
    public function getWafPackageRules(string $packageId): array
    {
        $response = $this->get($this->endpoints->wafPackageRules($packageId));

        return $this->mapper->extractResult($response);
    }

    /**
     * Obtiene todos los rulesets (OWASP/Custom).
     *
     * @deprecated Usar getRulesets() que sigue el naming del resto del servicio.
     */
    public function getWaf(): array
    {
        return $this->getRulesets();
    }

    /**
     * Obtiene todos los rulesets configurados (OWASP + Custom).
     */
    public function getRulesets(): array
    {
        $response = $this->get($this->endpoints->rulesets());

        return $this->mapper->extractResult($response);
    }

    // ================================================================
    // ZONE SETTINGS
    // ================================================================

    /**
     * Obtiene todas las settings de la zone.
     */
    public function getZoneSettings(): array
    {
        $response = $this->get($this->endpoints->zoneSettings());

        return $this->mapper->extractResult($response);
    }

    /**
     * Obtiene un setting específico.
     */
    public function getZoneSetting(string $settingName): array
    {
        $response = $this->get($this->endpoints->zoneSetting($settingName));

        return $this->mapper->extractSingleResult($response);
    }

    /**
     * Actualiza una setting de la zone.
     *
     * @throws CloudflareValidationException
     */
    public function updateZoneSetting(string $settingName, mixed $value): array
    {
        $response = $this->patch($this->endpoints->zoneSetting($settingName), [
            'value' => $value,
        ]);

        return $this->mapper->extractSingleResult($response);
    }

    // ================================================================
    // TUNNEL
    // ================================================================

    /**
     * Obtiene información de Cloudflare Tunnels.
     */
    public function getTunnelInformation(): array
    {
        $response = $this->get($this->endpoints->tunnels());

        return $this->mapper->extractResult($response);
    }

    /**
     * Obtiene un tunnel específico.
     */
    public function getTunnel(string $tunnelId): array
    {
        $response = $this->get($this->endpoints->tunnel($tunnelId));

        return $this->mapper->extractSingleResult($response);
    }

    // ================================================================
    // RATE LIMITS
    // ================================================================

    /**
     * Obtiene reglas de rate limiting.
     */
    public function getRateLimits(): array
    {
        $response = $this->get($this->endpoints->rateLimits());

        return $this->mapper->extractResult($response);
    }

    // ================================================================
    // IP ACCESS LISTS
    // ================================================================

    /**
     * Obtiene listas de IPs (IP Lists).
     */
    public function getIpLists(): array
    {
        $response = $this->get($this->endpoints->ipLists());

        return $this->mapper->extractResult($response);
    }

    /**
     * Obtiene items de una IP List.
     */
    public function getIpListItems(string $listId): array
    {
        $response = $this->get($this->endpoints->ipListItems($listId));

        return $this->mapper->extractResult($response);
    }

    // ================================================================
    // HEALTH CHECK
    // ================================================================

    /**
     * Verifica que el API token tenga los permisos mínimos necesarios.
     *
     * Realiza una llamada GET a /user/tokens/verify.
     */
    public function verifyToken(): array
    {
        $response = $this->get("{$this->endpoints->baseUrl()}/user/tokens/verify");

        return $this->mapper->validateAndExtract($response);
    }

    /**
     * Obtiene un resumen rápido del estado de la integración.
     *
     * @return array{status: string, zone: string, account_id: string, permissions: array}
     */
    public function getStatusSummary(): array
    {
        try {
            $verify = $this->verifyToken();

            $zone = $this->getZone();

            return [
                'status' => 'connected',
                'zone' => $zone->name,
                'zone_status' => $zone->status,
                'account_id' => $this->endpoints->accountId(),
                'permissions' => $verify['result']['scopes'] ?? [],
            ];
        } catch (CloudflareAuthenticationException $e) {
            return [
                'status' => 'unauthenticated',
                'zone' => '—',
                'zone_status' => '—',
                'account_id' => $this->endpoints->accountId(),
                'permissions' => [],
            ];
        } catch (CloudflareConnectionException $e) {
            return [
                'status' => 'connection_error',
                'zone' => '—',
                'zone_status' => '—',
                'account_id' => $this->endpoints->accountId(),
                'permissions' => [],
            ];
        } catch (\Throwable $e) {
            Log::warning('Cloudflare status summary error', [
                'message' => $e->getMessage(),
            ]);

            return [
                'status' => 'error',
                'zone' => '—',
                'zone_status' => '—',
                'account_id' => $this->endpoints->accountId(),
                'permissions' => [],
            ];
        }
    }

    // ================================================================
    // HTTP VERBS (PRIVATE)
    // ================================================================

    /**
     * Ejecuta una petición GET con manejo de errores de conexión.
     *
     * @throws CloudflareConnectionException
     * @throws CloudflareAuthenticationException
     */
    private function get(string $url): \Illuminate\Http\Client\Response
    {
        try {
            $response = $this->http->get($url);

            $this->checkHttpError($response, $url);

            return $response;
        } catch (RequestException $e) {
            $this->handleRequestException($e, $url);
        } catch (\Illuminate\Http\Client\ConnectionException $e) {
            throw new CloudflareConnectionException(
                'No se pudo conectar con la API de Cloudflare. Verifique la conectividad de red.',
                502,
                $e
            );
        }
    }

    /**
     * Ejecuta una petición POST.
     *
     * @throws CloudflareConnectionException
     * @throws CloudflareValidationException
     */
    private function post(string $url, array $data = []): \Illuminate\Http\Client\Response
    {
        try {
            $response = $this->http->post($url, $data);

            $this->checkHttpError($response, $url);

            if ($response->status() === 422) {
                $body = $response->json();
                $errors = $body['errors'] ?? [];

                throw new CloudflareValidationException(
                    errors: $errors,
                    message: 'Cloudflare rechazó la solicitud por datos inválidos.'
                );
            }

            return $response;
        } catch (RequestException $e) {
            $this->handleRequestException($e, $url);
        } catch (\Illuminate\Http\Client\ConnectionException $e) {
            throw new CloudflareConnectionException(
                'No se pudo conectar con la API de Cloudflare.',
                502,
                $e
            );
        }
    }

    private function patch(string $url, array $data = []): \Illuminate\Http\Client\Response
    {
        try {
            $response = $this->http->patch($url, $data);

            $this->checkHttpError($response, $url);

            if ($response->status() === 422) {
                $body = $response->json();
                $errors = $body['errors'] ?? [];

                throw new CloudflareValidationException(
                    errors: $errors,
                    message: 'Cloudflare rechazó la solicitud por datos inválidos.'
                );
            }

            return $response;
        } catch (RequestException $e) {
            $this->handleRequestException($e, $url);
        } catch (\Illuminate\Http\Client\ConnectionException $e) {
            throw new CloudflareConnectionException(
                'No se pudo conectar con la API de Cloudflare.',
                502,
                $e
            );
        }
    }

    private function delete(string $url): \Illuminate\Http\Client\Response
    {
        try {
            $response = $this->http->delete($url);

            $this->checkHttpError($response, $url);

            return $response;
        } catch (RequestException $e) {
            $this->handleRequestException($e, $url);
        } catch (\Illuminate\Http\Client\ConnectionException $e) {
            throw new CloudflareConnectionException(
                'No se pudo conectar con la API de Cloudflare.',
                502,
                $e
            );
        }
    }

    private function checkHttpError(\Illuminate\Http\Client\Response $response, string $url): void
    {
        $status = $response->status();

        if ($status === 401 || $status === 403) {
            $body = $response->json();
            $errorMessages = [];

            if (is_array($body) && isset($body['errors'])) {
                foreach ($body['errors'] as $err) {
                    $errorMessages[] = $err['message'] ?? 'Autenticación fallida';
                }
            }

            $message = ! empty($errorMessages)
                ? 'Cloudflare: '.implode('; ', $errorMessages)
                : 'Error de autenticación con Cloudflare. Verifique el API Token.';

            Log::warning('Cloudflare auth error', [
                'status' => $status,
                'endpoint' => $this->sanitizeUrlForLog($url),
            ]);

            throw new CloudflareAuthenticationException($message);
        }

        if ($status === 404) {
            throw new CloudflareNotFoundException(
                resource: "Endpoint: {$this->sanitizeUrlForLog($url)}"
            );
        }

        if ($status === 429) {
            $body = $response->json();
            $errorMessages = [];

            if (is_array($body) && isset($body['errors'])) {
                foreach ($body['errors'] as $err) {
                    $errorMessages[] = $err['message'] ?? 'Límite de tasa excedido';
                }
            }

            $retryAfter = (int) $response->header('Retry-After', '0');

            throw new CloudflareRateLimitException(
                message: ! empty($errorMessages) ? implode('; ', $errorMessages) : 'Límite de tasa de Cloudflare excedido.',
                retryAfter: $retryAfter
            );
        }

        if ($status >= 400) {
            $body = $response->json();
            $errorMessages = [];

            if (is_array($body) && isset($body['errors'])) {
                foreach ($body['errors'] as $err) {
                    $errorMessages[] = $err['message'] ?? "HTTP {$status}";
                }
            }

            $message = ! empty($errorMessages)
                ? 'Cloudflare: '.implode('; ', $errorMessages)
                : "Cloudflare respondió con HTTP {$status}.";

            Log::error('Cloudflare HTTP error', [
                'status' => $status,
                'endpoint' => $this->sanitizeUrlForLog($url),
                'errors' => $errorMessages,
            ]);

            throw new CloudflareValidationException(
                errors: $errorMessages,
                message: $message
            );
        }
    }

    private function handleRequestException(RequestException $e, string $url): never
    {
        $response = $e->response;
        $status = $response?->status() ?? 0;

        Log::error('Cloudflare request exception', [
            'status' => $status,
            'endpoint' => $this->sanitizeUrlForLog($url),
            'message' => $e->getMessage(),
        ]);

        if ($status === 401 || $status === 403) {
            throw new CloudflareAuthenticationException(
                'Error de autenticación con Cloudflare. Verifique el API Token.'
            );
        }

        if ($status === 404) {
            throw new CloudflareNotFoundException(
                resource: "Endpoint: {$this->sanitizeUrlForLog($url)}"
            );
        }

        if ($status === 429) {
            $retryAfter = (int) ($response?->header('Retry-After', '0'));
            throw new CloudflareRateLimitException(
                message: 'Límite de tasa de Cloudflare excedido.',
                retryAfter: $retryAfter
            );
        }

        if ($status >= 400) {
            throw new CloudflareValidationException(
                errors: [$e->getMessage()],
                message: "Cloudflare respondió con HTTP {$status}."
            );
        }

        throw new CloudflareConnectionException(
            "Error de conexión con Cloudflare: {$e->getMessage()}",
            502,
            $e
        );
    }

    /**
     * Limpia la URL para logging, ocultando IDs sensibles.
     */
    private function sanitizeUrlForLog(string $url): string
    {
        // Reemplaza valores UUID/hex largos con {id} para no loggear datos de infraestructura
        return (string) preg_replace(
            '/[a-f0-9]{32,}/i',
            '{id}',
            $url
        );
    }
}
