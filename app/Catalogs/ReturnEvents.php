<?php

declare(strict_types=1);

namespace App\Catalogs;

final class ReturnEvents
{
    const CREATED = 'returns.created';
    const APPROVED = 'returns.approved';
    const REJECTED = 'returns.rejected';
    const RECEIVED = 'returns.received';
    const REFUNDED = 'returns.refunded';
}
