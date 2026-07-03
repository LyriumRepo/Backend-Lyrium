<?php

declare(strict_types=1);

namespace App\Catalogs;

final class CouponEvents
{
    const CREATED = 'coupons.created';
    const UPDATED = 'coupons.updated';
    const DELETED = 'coupons.deleted';
    const REDEEMED = 'coupons.redeemed';
    const VALIDATED = 'coupons.validated';
}
