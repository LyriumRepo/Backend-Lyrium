<?php

declare(strict_types=1);

namespace App\DTO\Cloudflare;

/**
 * DTO que agrupa métricas de seguridad de Cloudflare.
 *
 * Incluye datos de threat, firewall events, WAF, rate limiting, bot management.
 */
final readonly class SecurityDTO
{
    /**
     * @param  FirewallEventDTO[]  $recentEvents
     */
    public function __construct(
        public int $totalThreats,
        public int $totalBlocked,
        public int $totalChallenges,
        public int $uniqueIps,
        public int $topAttackedPaths,
        public int $topAttackCountries,
        public int $recentEventCount,
        public array $recentEvents,
        public string $since,
        public string $until,
    ) {}

    public static function fromApiResponse(array $data, array $events): self
    {
        return new self(
            totalThreats: (int) ($data['threats']['all'] ?? 0),
            totalBlocked: (int) ($data['blocked']['all'] ?? 0),
            totalChallenges: (int) ($data['challenges']['all'] ?? 0),
            uniqueIps: (int) ($data['uniqueIps']['all'] ?? 0),
            topAttackedPaths: (int) ($data['topPaths']['all'] ?? 0),
            topAttackCountries: (int) ($data['topCountries']['all'] ?? 0),
            recentEventCount: count($events),
            recentEvents: $events,
            since: $data['since'] ?? '',
            until: $data['until'] ?? '',
        );
    }

    public function toArray(): array
    {
        return [
            'total_threats' => $this->totalThreats,
            'total_blocked' => $this->totalBlocked,
            'total_challenges' => $this->totalChallenges,
            'unique_ips' => $this->uniqueIps,
            'top_attacked_paths' => $this->topAttackedPaths,
            'top_attack_countries' => $this->topAttackCountries,
            'recent_event_count' => $this->recentEventCount,
            'recent_events' => array_map(fn (FirewallEventDTO $e) => $e->toArray(), $this->recentEvents),
            'since' => $this->since,
            'until' => $this->until,
        ];
    }
}
