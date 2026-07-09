<?php

declare(strict_types=1);

namespace App\DTO\Cloudflare;

/**
 * DTO que representa un evento de firewall (WAF, Rate Limit, IP Block, etc.).
 */
final readonly class FirewallEventDTO
{
    public function __construct(
        public string $id,
        public string $action,
        public string $kind,
        public string $source,
        public string $ipAddress,
        public ?string $country,
        public string $rayId,
        public string $host,
        public string $method,
        public string $path,
        public string $protocol,
        public ?string $userAgent,
        public int $httpResponseCode,
        public string $occurredAt,
    ) {}

    public static function fromApiResponse(array $data): self
    {
        return new self(
            id: $data['id'] ?? '',
            action: $data['action'] ?? '',
            kind: $data['kind'] ?? '',
            source: $data['source'] ?? '',
            ipAddress: $data['ip_address'] ?? $data['ip'] ?? '',
            country: $data['country'] ?? null,
            rayId: $data['ray_id'] ?? $data['rayId'] ?? '',
            host: $data['host'] ?? '',
            method: $data['method'] ?? '',
            path: $data['path'] ?? '',
            protocol: $data['protocol'] ?? '',
            userAgent: $data['user_agent'] ?? null,
            httpResponseCode: (int) ($data['http_response_code'] ?? $data['response_code'] ?? 0),
            occurredAt: $data['occurred_at'] ?? $data['timestamp'] ?? '',
        );
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'action' => $this->action,
            'kind' => $this->kind,
            'source' => $this->source,
            'ip_address' => $this->ipAddress,
            'country' => $this->country,
            'ray_id' => $this->rayId,
            'host' => $this->host,
            'method' => $this->method,
            'path' => $this->path,
            'protocol' => $this->protocol,
            'user_agent' => $this->userAgent,
            'http_response_code' => $this->httpResponseCode,
            'occurred_at' => $this->occurredAt,
        ];
    }
}
