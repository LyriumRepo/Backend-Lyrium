<?php

declare(strict_types=1);

namespace App\DTO\Cloudflare;

/**
 * DTO que representa las analíticas de un Zone en Cloudflare.
 *
 * Encapsula métricas de ancho de banda, peticiones, HTTP status codes,
 * visitantes únicos y datos de threat (seguridad).
 */
final readonly class AnalyticsDTO
{
    /**
     * @param  AnalyticsTimeseries[]  $timeseries
     */
    public function __construct(
        public array $timeseries,
        public AnalyticsTotals $totals,
        public string $since,
        public string $until,
    ) {}

    public static function fromApiResponse(array $data): self
    {
        $totalsData = $data['totals'] ?? [];
        $timeseriesData = $data['timeseries'] ?? [];

        $totals = new AnalyticsTotals(
            requests: (int) ($totalsData['requests']['all'] ?? 0),
            bandwidth: (float) ($totalsData['bandwidth']['all'] ?? 0),
            threats: (int) ($totalsData['threats']['all'] ?? 0),
            pageViews: (int) ($totalsData['pageViews']['all'] ?? 0),
            uniques: (int) ($totalsData['uniques']['all'] ?? 0),
            requestsCached: (int) ($totalsData['requests']['cached'] ?? 0),
            requestsUncached: (int) ($totalsData['requests']['uncached'] ?? 0),
            status200: (int) ($totalsData['responseStatuses'][200] ?? 0),
            status300: (int) ($totalsData['responseStatuses'][300] ?? 0),
            status400: (int) ($totalsData['responseStatuses'][400] ?? 0),
            status500: (int) ($totalsData['responseStatuses'][500] ?? 0),
        );

        $timeseries = array_map(
            fn (array $item) => AnalyticsTimeseries::fromApiResponse($item),
            $timeseriesData
        );

        return new self(
            timeseries: $timeseries,
            totals: $totals,
            since: $data['since'] ?? '',
            until: $data['until'] ?? '',
        );
    }

    public function toArray(): array
    {
        return [
            'totals' => $this->totals->toArray(),
            'timeseries' => array_map(fn (AnalyticsTimeseries $ts) => $ts->toArray(), $this->timeseries),
            'since' => $this->since,
            'until' => $this->until,
        ];
    }

    public function cacheRatio(): float
    {
        $total = $this->totals->requests;

        return $total > 0 ? round(($this->totals->requestsCached / $total) * 100, 2) : 0.0;
    }

    public function threatPercentage(): float
    {
        $total = $this->totals->requests;

        return $total > 0 ? round(($this->totals->threats / $total) * 100, 2) : 0.0;
    }
}

/**
 * DTO que representa los totales agregados de analíticas.
 */
final readonly class AnalyticsTotals
{
    public function __construct(
        public int $requests,
        public float $bandwidth,
        public int $threats,
        public int $pageViews,
        public int $uniques,
        public int $requestsCached,
        public int $requestsUncached,
        public int $status200,
        public int $status300,
        public int $status400,
        public int $status500,
    ) {}

    public function toArray(): array
    {
        return [
            'requests' => $this->requests,
            'bandwidth' => $this->bandwidth,
            'threats' => $this->threats,
            'page_views' => $this->pageViews,
            'uniques' => $this->uniques,
            'requests_cached' => $this->requestsCached,
            'requests_uncached' => $this->requestsUncached,
            'status_200' => $this->status200,
            'status_300' => $this->status300,
            'status_400' => $this->status400,
            'status_500' => $this->status500,
        ];
    }
}

/**
 * DTO que representa un punto en la serie temporal de analíticas.
 */
final readonly class AnalyticsTimeseries
{
    public function __construct(
        public string $since,
        public string $until,
        public int $requests,
        public float $bandwidth,
        public int $threats,
        public int $pageViews,
        public int $uniques,
    ) {}

    public static function fromApiResponse(array $data): self
    {
        return new self(
            since: $data['since'] ?? '',
            until: $data['until'] ?? '',
            requests: (int) ($data['requests']['all'] ?? 0),
            bandwidth: (float) ($data['bandwidth']['all'] ?? 0),
            threats: (int) ($data['threats']['all'] ?? 0),
            pageViews: (int) ($data['pageViews']['all'] ?? 0),
            uniques: (int) ($data['uniques']['all'] ?? 0),
        );
    }

    public function toArray(): array
    {
        return [
            'since' => $this->since,
            'until' => $this->until,
            'requests' => $this->requests,
            'bandwidth' => $this->bandwidth,
            'threats' => $this->threats,
            'page_views' => $this->pageViews,
            'uniques' => $this->uniques,
        ];
    }
}
