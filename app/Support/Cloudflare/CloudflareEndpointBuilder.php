<?php

declare(strict_types=1);

namespace App\Support\Cloudflare;

/**
 * Construye endpoints de la API v4 de Cloudflare de forma consistente.
 *
 * Responsabilidad: generar URLs completas usando base_url, account_id y zone_id.
 * No realiza peticiones HTTP.
 */
final readonly class CloudflareEndpointBuilder
{
    private string $baseUrl;

    private string $zoneId;

    private string $accountId;

    public function __construct()
    {
        $this->baseUrl = rtrim((string) config('cloudflare.base_url', 'https://api.cloudflare.com/client/v4'), '/');
        $this->zoneId = (string) config('cloudflare.zone_id');
        $this->accountId = (string) config('cloudflare.account_id');
    }

    /**
     * Endpoint para obtener/metadata de la zone.
     */
    public function zone(): string
    {
        return "{$this->baseUrl}/zones/{$this->zoneId}";
    }

    /**
     * Endpoint para analíticas (HTTP requests, bandwidth, threats).
     */
    public function analytics(string $since = '', string $until = ''): string
    {
        $path = "{$this->baseUrl}/zones/{$this->zoneId}/analytics/dashboard";
        $params = [];

        if ($since) {
            $params['since'] = $since;
        }

        if ($until) {
            $params['until'] = $until;
        }

        return $params ? $path.'?'.http_build_query($params) : $path;
    }

    /**
     * Endpoint para eventos de firewall (WAF, rate limit, IP blocks).
     */
    public function firewallEvents(int $perPage = 50, int $page = 1): string
    {
        return "{$this->baseUrl}/zones/{$this->zoneId}/security/events?per_page={$perPage}&page={$page}";
    }

    /**
     * Endpoint para registros DNS.
     */
    public function dnsRecords(string $search = ''): string
    {
        $path = "{$this->baseUrl}/zones/{$this->zoneId}/dns_records";

        if ($search) {
            $path .= '?'.http_build_query(['search' => $search]);
        }

        return $path;
    }

    /**
     * Endpoint para un registro DNS específico.
     */
    public function dnsRecord(string $recordId): string
    {
        return "{$this->baseUrl}/zones/{$this->zoneId}/dns_records/{$recordId}";
    }

    /**
     * Endpoint para purgar caché.
     */
    public function purgeCache(): string
    {
        return "{$this->baseUrl}/zones/{$this->zoneId}/purge_cache";
    }

    /**
     * Endpoint para WAF (Web Application Firewall) packages.
     */
    public function wafPackages(): string
    {
        return "{$this->baseUrl}/zones/{$this->zoneId}/firewall/waf/packages";
    }

    /**
     * Endpoint para reglas de un paquete WAF específico.
     */
    public function wafPackageRules(string $packageId): string
    {
        return "{$this->baseUrl}/zones/{$this->zoneId}/firewall/waf/packages/{$packageId}/rules";
    }

    /**
     * Endpoint para obtener/configurar zone settings (SSL, min TLS, etc.).
     */
    public function zoneSettings(): string
    {
        return "{$this->baseUrl}/zones/{$this->zoneId}/settings";
    }

    /**
     * Endpoint para un setting específico.
     */
    public function zoneSetting(string $settingName): string
    {
        return "{$this->baseUrl}/zones/{$this->zoneId}/settings/{$settingName}";
    }

    /**
     * Endpoint para obtener información de Cloudflare Tunnel.
     */
    public function tunnels(): string
    {
        return "{$this->baseUrl}/accounts/{$this->accountId}/cfd_tunnel";
    }

    /**
     * Endpoint para un tunnel específico.
     */
    public function tunnel(string $tunnelId): string
    {
        return "{$this->baseUrl}/accounts/{$this->accountId}/cfd_tunnel/{$tunnelId}";
    }

    /**
     * Endpoint para analíticas de seguridad (top paths, top countries, etc.).
     */
    public function securityAnalytics(string $since = '', string $until = ''): string
    {
        $path = "{$this->baseUrl}/zones/{$this->zoneId}/analytics/colos";

        $params = [];
        if ($since) {
            $params['since'] = $since;
        }
        if ($until) {
            $params['until'] = $until;
        }

        return $params ? $path.'?'.http_build_query($params) : $path;
    }

    /**
     * Endpoint para obtener reglas de Rate Limiting.
     */
    public function rateLimits(): string
    {
        return "{$this->baseUrl}/zones/{$this->zoneId}/rate_limits";
    }

    /**
     * Endpoint para obtener reglas de IP Access (IP Lists).
     */
    public function ipLists(): string
    {
        return "{$this->baseUrl}/accounts/{$this->accountId}/rules/lists";
    }

    /**
     * Endpoint para items de una IP List.
     */
    public function ipListItems(string $listId): string
    {
        return "{$this->baseUrl}/accounts/{$this->accountId}/rules/lists/{$listId}/items";
    }

    /**
     * Endpoint para OWASP/Custom Rulesets (WAF moderno).
     */
    public function rulesets(): string
    {
        return "{$this->baseUrl}/zones/{$this->zoneId}/rulesets";
    }

    public function baseUrl(): string
    {
        return $this->baseUrl;
    }

    public function zoneId(): string
    {
        return $this->zoneId;
    }

    public function accountId(): string
    {
        return $this->accountId;
    }
}
