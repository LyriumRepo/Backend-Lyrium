<?php

declare(strict_types=1);

namespace App\Catalogs;

final class ProductEvents
{
    const CREATED = 'products.created';
    const UPDATED = 'products.updated';
    const DELETED = 'products.deleted';
    const RESTORED = 'products.restored';
    const STATUS_CHANGED = 'products.status.changed';
    const PRICE_CHANGED = 'products.price.changed';
    const STOCK_CHANGED = 'products.stock.changed';
    const MEDIA_UPLOADED = 'products.media.uploaded';
    const MEDIA_DELETED = 'products.media.deleted';
    const ATTRIBUTES_UPDATED = 'products.attributes.updated';
}
