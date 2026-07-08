<?php

declare(strict_types=1);

namespace App\DTO\Cloudflare;

/**
 * DTO que representa un registro DNS de Cloudflare.
 */
final readonly class DNSRecordDTO
{
    public function __construct(
        public string $id,
        public string $type,
        public string $name,
        public string $content,
        public int $ttl,
        public bool $proxied,
        public ?int $priority,
        public string $createdOn,
        public string $modifiedOn,
        public ?string $comment,
        public array $tags,
    ) {}

    public static function fromApiResponse(array $data): self
    {
        return new self(
            id: $data['id'] ?? '',
            type: $data['type'] ?? '',
            name: $data['name'] ?? '',
            content: $data['content'] ?? '',
            ttl: (int) ($data['ttl'] ?? 120),
            proxied: (bool) ($data['proxied'] ?? false),
            priority: isset($data['priority']) ? (int) $data['priority'] : null,
            createdOn: $data['created_on'] ?? '',
            modifiedOn: $data['modified_on'] ?? '',
            comment: $data['comment'] ?? null,
            tags: $data['tags'] ?? [],
        );
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'type' => $this->type,
            'name' => $this->name,
            'content' => $this->content,
            'ttl' => $this->ttl,
            'proxied' => $this->proxied,
            'priority' => $this->priority,
            'created_on' => $this->createdOn,
            'modified_on' => $this->modifiedOn,
            'comment' => $this->comment,
            'tags' => $this->tags,
        ];
    }

    public function isProxied(): bool
    {
        return $this->proxied;
    }

    public function ttlLabel(): string
    {
        return $this->ttl === 1 ? 'Auto' : "{$this->ttl}s";
    }
}
