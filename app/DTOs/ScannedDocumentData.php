<?php

declare(strict_types=1);

namespace App\DTOs;

final readonly class IssuerData
{
    public function __construct(
        public ?string $name = null,
        public ?string $ruc = null,
        public ?string $address = null,
    ) {}
}

final readonly class CustomerData
{
    public function __construct(
        public ?string $name = null,
        public ?string $ruc = null,
        public ?string $address = null,
    ) {}
}

final readonly class ItemData
{
    public function __construct(
        public string $description,
        public float $quantity = 1.0,
        public ?float $unitPrice = null,
        public ?float $total = null,
    ) {}
}

final readonly class PaymentData
{
    public function __construct(
        public ?string $paymentMethod = null,
        public ?string $amountWords = null,
        public ?float $grossAmount = null,
        public ?float $retentionIr = null,
        public ?float $netAmount = null,
        public ?string $currency = null,
    ) {}
}

final readonly class TotalsData
{
    public function __construct(
        public ?float $taxableAmount = null,
        public ?float $inafectAmount = null,
        public ?float $exemptAmount = null,
        public ?float $freeAmount = null,
        public ?float $igv = null,
        public ?float $isc = null,
        public ?float $icbper = null,
        public ?float $otherTaxes = null,
        public ?float $otherCharges = null,
        public ?float $discounts = null,
        public ?float $grandTotal = null,
    ) {}
}

final readonly class ScannedDocumentData
{
    public function __construct(
        public string $rawText,

        // Core document info
        public ?string $documentType = null,
        public ?string $documentNumber = null,
        public ?string $issueDate = null,
        public ?string $dueDate = null,
        public ?string $currency = null,

        // Parties
        public ?IssuerData $issuer = null,
        public ?CustomerData $customer = null,

        // Financial
        public ?PaymentData $payment = null,
        public ?TotalsData $totals = null,
        public ?string $amountInWords = null,

        // Items & service
        public array $items = [],
        public ?string $serviceDescription = null,

        // Bank statement
        public array $bankStatementLines = [],
        public ?string $period = null,
        public ?float $openingBalance = null,
        public ?float $closingBalance = null,

        // Metadata
        public bool $isScannedImage = false,
        public ?string $authorizationDate = null,
        public string $source = 'PDF_TEXT',
    ) {}
}
