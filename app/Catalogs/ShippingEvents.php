<?php

declare(strict_types=1);

namespace App\Catalogs;

final class ShippingEvents
{
    const CREATED = 'shipments.created';
    const STATUS_CHANGED = 'shipments.status.changed';
    const TRACKING_UPDATED = 'shipments.tracking.updated';
    const ZONES_UPDATED = 'shipping.zones.updated';
    const RATES_UPDATED = 'shipping.rates.updated';
    const METHODS_UPDATED = 'shipping.methods.updated';
}
