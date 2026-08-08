<?php

declare(strict_types=1);

namespace App\Contracts;

interface InvoiceProviderInterface
{
    public function emitInvoice(array $payload): array;

    public function getInvoiceStatus(string $tipoDeComprobante, string $serie, string $numero): ?array;

    public function getInvoicePdfUrl(?string $providerInvoiceId): ?string;

    public function getInvoiceXmlUrl(?string $providerInvoiceId): ?string;

    public function getCdrUrl(?string $providerInvoiceId): ?string;

    /** @return array<array{type: string, series: string, label: string}> */
    public function getAvailableSeries(): array;
}
