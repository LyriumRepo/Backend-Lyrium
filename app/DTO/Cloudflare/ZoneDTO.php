<?php

declare(strict_types=1);

namespace App\DTO\Cloudflare;

/**
 * DTO que representa una Zone de Cloudflare.
 */
final readonly class ZoneDTO
{
    public function __construct(
        public string $id,
        public string $name,
        public string $status,
        public bool $paused,
        public string $type,
        public array $nameServers,
        public string $originalNameServer,
        public string $originalRegistrar,
        public ?string $plan,
        public string $createdOn,
        public string $modifiedOn,
    ) {}

    /**
     * Crea una instancia desde la respuesta de la API de Cloudflare.
     */
    public static function fromApiResponse(array $data): self
    {
        return new self(
            id: $data['id'] ?? '',
            name: $data['name'] ?? '',
            status: $data['status'] ?? 'unknown',
            paused: (bool) ($data['paused'] ?? false),
            type: $data['type'] ?? 'full',
            nameServers: $data['name_servers'] ?? [],
            originalNameServer: $data['original_name_servers'][0] ?? '',
            originalRegistrar: $data['original_registrar'] ?? '',
            plan: $data['plan']['name'] ?? null,
            createdOn: $data['created_on'] ?? '',
            modifiedOn: $data['modified_on'] ?? '',
        );
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'status' => $this->status,
            'paused' => $this->paused,
            'type' => $this->type,
            'name_servers' => $this->nameServers,
            'original_name_server' => $this->originalNameServer,
            'original_registrar' => $this->originalRegistrar,
            'plan' => $this->plan,
            'created_on' => $this->createdOn,
            'modified_on' => $this->modifiedOn,
        ];
    }

    public function isActive(): bool
    {
        return $this->status === 'active';
    }
}
