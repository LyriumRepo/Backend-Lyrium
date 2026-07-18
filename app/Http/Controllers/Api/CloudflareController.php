<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Exceptions\Cloudflare\CloudflareAuthenticationException;
use App\Exceptions\Cloudflare\CloudflareConnectionException;
use App\Exceptions\Cloudflare\CloudflareNotFoundException;
use App\Exceptions\Cloudflare\CloudflareRateLimitException;
use App\Exceptions\Cloudflare\CloudflareValidationException;
use App\Http\Controllers\Controller;
use App\Services\CloudflareService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

/**
 * Controlador para el módulo de integración con Cloudflare.
 *
 * Responsabilidad: validar requests y devolver JSON.
 * Sin lógica de negocio — todo delegado a CloudflareService.
 */
final class CloudflareController extends Controller
{
    public function __construct(
        private readonly CloudflareService $cloudflare,
    ) {}

    /**
     * Estado general de la integración.
     */
    public function status(): JsonResponse
    {
        try {
            $summary = $this->cloudflare->getStatusSummary();

            return $this->success($summary);
        } catch (\Throwable $e) {
            return $this->errorWithCode('CLOUDFLARE_ERROR', $e->getMessage(), 502);
        }
    }

    /**
     * Información de la zone.
     */
    public function zone(): JsonResponse
    {
        return $this->handleRequest(fn () => [
            'zone' => $this->cloudflare->getZone()->toArray(),
        ]);
    }

    /**
     * Analíticas del dashboard.
     */
    public function analytics(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'since' => ['nullable', 'string', 'date_format:Y-m-d\TH:i:s\Z'],
            'until' => ['nullable', 'string', 'date_format:Y-m-d\TH:i:s\Z'],
        ]);

        if ($validator->fails()) {
            return $this->validationError($validator->errors());
        }

        return $this->handleRequest(fn () => [
            'analytics' => $this->cloudflare->getAnalytics(
                since: (string) $request->string('since', ''),
                until: (string) $request->string('until', ''),
            )->toArray(),
        ]);
    }

    /**
     * Analíticas de seguridad.
     */
    public function securityAnalytics(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'since' => ['nullable', 'string', 'date_format:Y-m-d\TH:i:s\Z'],
            'until' => ['nullable', 'string', 'date_format:Y-m-d\TH:i:s\Z'],
        ]);

        if ($validator->fails()) {
            return $this->validationError($validator->errors());
        }

        return $this->handleRequest(fn () => [
            'security' => $this->cloudflare->getSecurityAnalytics(
                since: (string) $request->string('since', ''),
                until: (string) $request->string('until', ''),
            )->toArray(),
        ]);
    }

    /**
     * Eventos de firewall.
     */
    public function firewallEvents(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
            'page' => ['nullable', 'integer', 'min:1'],
        ]);

        if ($validator->fails()) {
            return $this->validationError($validator->errors());
        }

        $events = $this->cloudflare->getFirewallEvents(
            perPage: (int) $request->integer('per_page', 50),
            page: (int) $request->integer('page', 1),
        );

        return $this->success([
            'events' => array_map(fn ($e) => $e->toArray(), $events),
            'total' => count($events),
        ]);
    }

    /**
     * Lista de registros DNS.
     */
    public function dnsRecords(Request $request): JsonResponse
    {
        $search = (string) $request->string('search', '');

        $records = $this->cloudflare->getDnsRecords($search);

        return $this->success([
            'records' => array_map(fn ($r) => $r->toArray(), $records),
            'total' => count($records),
        ]);
    }

    /**
     * Crear registro DNS.
     */
    public function createDnsRecord(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'type' => ['required', 'string', 'in:A,AAAA,CNAME,MX,TXT,NS,SRV,CAA,PTR'],
            'name' => ['required', 'string', 'max:255'],
            'content' => ['required', 'string', 'max:500'],
            'ttl' => ['nullable', 'integer', 'min:60', 'max:86400'],
            'proxied' => ['nullable', 'boolean'],
            'comment' => ['nullable', 'string', 'max:100'],
        ]);

        if ($validator->fails()) {
            return $this->validationError($validator->errors());
        }

        return $this->handleRequest(fn () => [
            'record' => $this->cloudflare->createDnsRecord($request->only([
                'type', 'name', 'content', 'ttl', 'proxied', 'comment',
            ]))->toArray(),
        ]);
    }

    /**
     * Actualizar registro DNS.
     */
    public function updateDnsRecord(Request $request, string $recordId): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'type' => ['required', 'string', 'in:A,AAAA,CNAME,MX,TXT,NS,SRV,CAA,PTR'],
            'name' => ['required', 'string', 'max:255'],
            'content' => ['required', 'string', 'max:500'],
            'ttl' => ['nullable', 'integer', 'min:60', 'max:86400'],
            'proxied' => ['nullable', 'boolean'],
            'comment' => ['nullable', 'string', 'max:100'],
        ]);

        if ($validator->fails()) {
            return $this->validationError($validator->errors());
        }

        return $this->handleRequest(fn () => [
            'record' => $this->cloudflare->updateDnsRecord($recordId, $request->only([
                'type', 'name', 'content', 'ttl', 'proxied', 'comment',
            ]))->toArray(),
        ]);
    }

    /**
     * Eliminar registro DNS.
     */
    public function deleteDnsRecord(string $recordId): JsonResponse
    {
        return $this->handleRequest(fn () => [
            'deleted' => $this->cloudflare->deleteDnsRecord($recordId),
        ]);
    }

    /**
     * Purgar caché.
     */
    public function purgeCache(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'type' => ['nullable', 'string', 'in:everything,files,tags'],
            'files' => ['nullable', 'array'],
            'files.*' => ['required', 'string', 'url'],
            'tags' => ['nullable', 'array'],
            'tags.*' => ['required', 'string', 'max:255'],
        ]);

        if ($validator->fails()) {
            return $this->validationError($validator->errors());
        }

        return $this->handleRequest(function () use ($request): array {
            $type = (string) $request->string('type', 'everything');

            $result = match ($type) {
                'files' => $this->cloudflare->purgeCacheFiles($request->array('files', [])),
                'tags' => $this->cloudflare->purgeCacheByTags($request->array('tags', [])),
                default => $this->cloudflare->purgeCache(),
            };

            return ['purged' => $result, 'type' => $type];
        });
    }

    /**
     * Zone settings.
     */
    public function zoneSettings(): JsonResponse
    {
        return $this->handleRequest(fn () => [
            'settings' => $this->cloudflare->getZoneSettings(),
        ]);
    }

    /**
     * Actualizar zone setting.
     */
    public function updateZoneSetting(Request $request, string $settingName): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'value' => ['required'],
        ]);

        if ($validator->fails()) {
            return $this->validationError($validator->errors());
        }

        return $this->handleRequest(fn () => [
            'setting' => $this->cloudflare->updateZoneSetting(
                $settingName,
                $request->input('value')
            ),
        ]);
    }

    /**
     * WAF / Rulesets.
     */
    public function waf(): JsonResponse
    {
        return $this->handleRequest(fn () => [
            'rulesets' => $this->cloudflare->getWaf(),
        ]);
    }

    /**
     * Cloudflare Tunnels.
     */
    public function tunnels(): JsonResponse
    {
        return $this->handleRequest(fn () => [
            'tunnels' => $this->cloudflare->getTunnelInformation(),
        ]);
    }

    /**
     * Rate Limits.
     */
    public function rateLimits(): JsonResponse
    {
        return $this->handleRequest(fn () => [
            'rate_limits' => $this->cloudflare->getRateLimits(),
        ]);
    }

    /**
     * IP Lists.
     */
    public function ipLists(): JsonResponse
    {
        return $this->handleRequest(fn () => [
            'ip_lists' => $this->cloudflare->getIpLists(),
        ]);
    }

    // ================================================================
    // HELPERS
    // ================================================================

    /**
     * Envuelve una llamada al servicio con manejo de excepciones.
     *
     * @param  callable(): array  $callback
     */
    private function handleRequest(callable $callback): JsonResponse
    {
        try {
            $data = $callback();

            return $this->success($data);
        } catch (CloudflareAuthenticationException $e) {
            return $this->errorWithCode('CLOUDFLARE_AUTH_ERROR', $e->getMessage(), 401);
        } catch (CloudflareConnectionException $e) {
            return $this->errorWithCode('CLOUDFLARE_CONNECTION_ERROR', $e->getMessage(), 502);
        } catch (CloudflareRateLimitException $e) {
            $message = $e->retryAfter
                ? "Límite de tasa excedido. Intente en {$e->retryAfter} segundos."
                : $e->getMessage();

            return $this->errorWithCode('CLOUDFLARE_RATE_LIMIT', $message, 429);
        } catch (CloudflareValidationException $e) {
            return $this->error($e->getMessage(), 422, $e->getErrors());
        } catch (CloudflareNotFoundException $e) {
            return $this->errorWithCode('CLOUDFLARE_NOT_FOUND', $e->getMessage(), 404);
        } catch (\Throwable $e) {
            Log::error('Cloudflare: error inesperado', [
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ]);

            return $this->errorWithCode('CLOUDFLARE_UNEXPECTED_ERROR', 'Error inesperado en la integración con Cloudflare.', 500);
        }
    }
}
