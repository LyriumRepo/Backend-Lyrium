<?php

declare(strict_types=1);

namespace App\Catalogs;

final class InvoiceEvents
{
    const GENERATED = 'invoices.generated';
    const SENT_TO_SUNAT = 'invoices.sent.to.sunat';
    const ACCEPTED = 'invoices.accepted';
    const OBSERVED = 'invoices.observed';
    const REJECTED = 'invoices.rejected';
    const RETRIED = 'invoices.retried';
}
