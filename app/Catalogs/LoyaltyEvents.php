<?php

declare(strict_types=1);

namespace App\Catalogs;

final class LoyaltyEvents
{
    const POINTS_EARNED = 'loyalty.points.earned';
    const POINTS_REDEEMED = 'loyalty.points.redeemed';
    const POINTS_EXPIRED = 'loyalty.points.expired';
    const TIER_CHANGED = 'loyalty.tier.changed';
    const REWARD_CREATED = 'loyalty.reward.created';
    const REWARD_UPDATED = 'loyalty.reward.updated';
    const REWARD_DELETED = 'loyalty.reward.deleted';
    const REWARD_REDEEMED = 'loyalty.reward.redeemed';
}
