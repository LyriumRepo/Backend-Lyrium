<?php

declare(strict_types=1);

namespace App\DTOs;

final readonly class BankStatementLineData
{
    public function __construct(
        public string $date,
        public string $description,
        public ?float $amount = null,
        public ?float $balance = null,
        public ?string $reference = null,
        public ?float $charge = null,
        public ?float $deposit = null,
        public ?string $glossaryKey = null,
        public ?string $glossaryDescription = null,
        public ?int $suggestedSupplierId = null,

        // Structured fields from BCP layout
        public ?string $hour = null,
        public ?string $med = null,
        public ?string $tipo = null,
        public ?string $place = null,
        public ?string $origen = null,
        public ?string $numOp = null,
        public ?string $sucAge = null,
    ) {}
}
