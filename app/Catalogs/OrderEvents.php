<?php

declare(strict_types=1);

namespace App\Catalogs;

final class OrderEvents
{
    const CREATED = 'orders.created';
    const STATUS_CHANGED = 'orders.status.changed';
    const CANCELLED = 'orders.cancelled';
    const REFUNDED = 'orders.refunded';
    const REFUND_PARTIAL = 'orders.refund.partial';
    const SHIPPED = 'orders.shipped';
    const DELIVERED = 'orders.delivered';
    const ITEM_STATUS_CHANGED = 'orders.item.status.changed';
    const PAYMENT_CONFIRMED = 'orders.payment.confirmed';
    const PAYMENT_FAILED = 'orders.payment.failed';
    const RECEIPT_REQUESTED = 'orders.receipt.requested';
}
