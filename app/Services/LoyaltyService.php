<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\LoyaltyProgram;
use App\Models\LoyaltyReward;
use App\Models\LoyaltyTransaction;
use App\Models\UserLoyaltyAccount;
use App\Models\UserRedeemedReward;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

final class LoyaltyService
{
    private const DEFAULT_PER_PAGE = 15;

    private const MAX_PER_PAGE = 100;

    public function getOrCreateAccount(int $userId, int $programId = 1): UserLoyaltyAccount
    {
        $account = UserLoyaltyAccount::query()
            ->where('user_id', $userId)
            ->where('program_id', $programId)
            ->first();

        if ($account) {
            return $account;
        }

        $program = LoyaltyProgram::findOrFail($programId);
        $defaultTier = $program->tiers()->where('is_default', true)->first();

        return UserLoyaltyAccount::create([
            'user_id' => $userId,
            'program_id' => $programId,
            'tier_id' => $defaultTier?->id,
            'points_balance' => 0,
            'lifetime_points' => 0,
            'points_redeemed' => 0,
        ]);
    }

    public function getAccount(int $userId, int $programId = 1): ?UserLoyaltyAccount
    {
        return UserLoyaltyAccount::query()
            ->where('user_id', $userId)
            ->where('program_id', $programId)
            ->with(['tier', 'program'])
            ->first();
    }

    public function earnPointsFromOrder(int $userId, float $orderTotal, int $orderId): UserLoyaltyAccount
    {
        $account = $this->getOrCreateAccount($userId);
        $program = $account->program;

        $points = $program->calculatePointsForAmount($orderTotal);

        if ($account->tier && $account->tier->bonus_rate > 0) {
            $bonusPoints = (int) floor($points * ($account->tier->bonus_rate / 100));
            $points += $bonusPoints;
        }

        $account->addPoints($points, $orderId, "Compra #{$orderId}");

        return $account->fresh(['tier', 'program']);
    }

    public function redeemPoints(
        int $userId,
        int $rewardId,
        ?int $pointsToRedeem = null
    ): UserRedeemedReward {
        $account = $this->getOrCreateAccount($userId);
        $program = $account->program;

        $reward = LoyaltyReward::findOrFail($rewardId);

        if (! $reward->isAvailable()) {
            throw new \InvalidArgumentException('Esta recompensa no está disponible');
        }

        if ($account->points_balance < $reward->points_required) {
            throw new \InvalidArgumentException('Puntos insuficientes');
        }

        $points = $pointsToRedeem ?? $reward->points_required;

        if ($points > $account->points_balance) {
            throw new \InvalidArgumentException('Puntos insuficientes');
        }

        DB::transaction(function () use ($account, $reward, $points): void {
            $account->redeemPoints($points, "Canjeo: {$reward->name}");

            $discountValue = $reward->calculateDiscountValue(100);

            $redemption = UserRedeemedReward::create([
                'user_id' => $account->user_id,
                'reward_id' => $reward->id,
                'code' => UserRedeemedReward::generateCode(),
                'discount_value' => $discountValue,
                'valid_until' => now()->addMonths(3),
            ]);

            $reward->increment('uses_count');
        });

        return $account->fresh(['tier', 'program']);
    }

    public function getAvailableRewards(int $programId = 1)
    {
        $program = LoyaltyProgram::findOrFail($programId);

        return $program->activeRewards()->get();
    }

    public function getUserRedemptions(int $userId, int $perPage = self::DEFAULT_PER_PAGE): LengthAwarePaginator
    {
        $perPage = min($perPage, self::MAX_PER_PAGE);

        return UserRedeemedReward::query()
            ->where('user_id', $userId)
            ->with(['reward'])
            ->orderBy('created_at', 'desc')
            ->paginate($perPage);
    }

    public function getUserTransactions(
        int $userId,
        int $programId = 1,
        int $perPage = self::DEFAULT_PER_PAGE
    ): LengthAwarePaginator {
        $perPage = min($perPage, self::MAX_PER_PAGE);

        $account = $this->getAccount($userId, $programId);

        if (! $account) {
            return new \Illuminate\Pagination\LengthAwarePaginator([], 0, $perPage);
        }

        return $account->transactions()
            ->with(['order'])
            ->orderBy('created_at', 'desc')
            ->paginate($perPage);
    }

    public function validateRewardCode(string $code, float $orderTotal): ?array
    {
        $redemption = UserRedeemedReward::query()
            ->where('code', $code)
            ->with(['reward', 'reward.program'])
            ->first();

        if (! $redemption || ! $redemption->isValid()) {
            return null;
        }

        $discountValue = $redemption->reward->calculateDiscountValue($orderTotal);

        return [
            'redemption' => $redemption,
            'discount_value' => $discountValue,
            'reward_type' => $redemption->reward->reward_type,
        ];
    }

    public function useRewardCode(string $code): UserRedeemedReward
    {
        $redemption = UserRedeemedReward::query()
            ->where('code', $code)
            ->firstOrFail();

        if (! $redemption->isValid()) {
            throw new \InvalidArgumentException('Código inválido o expirado');
        }

        $redemption->markAsUsed();

        return $redemption->fresh(['reward']);
    }

    public function expireOldPoints(): int
    {
        $expiredCount = 0;

        LoyaltyTransaction::query()
            ->where('type', 'earned')
            ->whereNotNull('expires_at')
            ->where('expires_at', '<=', now())
            ->where('points', '>', 0)
            ->chunk(100, function ($transactions) use (&$expiredCount): void {
                foreach ($transactions as $transaction) {
                    $account = $transaction->account;
                    if ($account && $account->points_balance >= $transaction->points) {
                        $account->decrement('points_balance', $transaction->points);
                        $transaction->update(['type' => 'expired']);
                        $expiredCount++;
                    }
                }
            });

        return $expiredCount;
    }

    public function getProgramStats(int $programId = 1): array
    {
        $program = LoyaltyProgram::findOrFail($programId);

        return [
            'program' => $program,
            'total_accounts' => UserLoyaltyAccount::where('program_id', $programId)->count(),
            'total_points_earned' => LoyaltyTransaction::whereHas('account', function ($query) use ($programId): void {
                $query->where('program_id', $programId);
            })->where('type', 'earned')->sum('points'),
            'total_points_redeemed' => LoyaltyTransaction::whereHas('account', function ($query) use ($programId): void {
                $query->where('program_id', $programId);
            })->where('type', 'redeemed')->sum('points'),
            'active_rewards' => $program->activeRewards()->count(),
        ];
    }

    public function getUserStatus(int $userId, int $programId = 1): ?array
    {
        $account = $this->getAccount($userId, $programId);

        if (! $account) {
            return null;
        }

        return $account->getStatus();
    }
}
