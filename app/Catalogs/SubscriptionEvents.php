<?php

declare(strict_types=1);

namespace App\Catalogs;

final class SubscriptionEvents
{
    const CREATED = 'subscriptions.created';
    const CHANGED = 'subscriptions.changed';
    const CANCELLED = 'subscriptions.cancelled';
    const EXPIRED = 'subscriptions.expired';
    const PAYMENT_SCHEDULED = 'subscriptions.payment.scheduled';
    const PAYMENT_COMPLETED = 'subscriptions.payment.completed';
    const PAYMENT_FAILED = 'subscriptions.payment.failed';
}
