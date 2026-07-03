<?php

declare(strict_types=1);

namespace App\Catalogs;

final class PaymentEvents
{
    const METHOD_CREATED = 'payments.method.created';
    const METHOD_DELETED = 'payments.method.deleted';
    const TRANSACTION_COMPLETED = 'payments.transaction.completed';
    const TRANSACTION_FAILED = 'payments.transaction.failed';
    const WEBHOOK_RECEIVED = 'payments.webhook.received';
    const PAYOUT_PROCESSED = 'payments.payout.processed';
    const PAYOUT_FAILED = 'payments.payout.failed';
}
