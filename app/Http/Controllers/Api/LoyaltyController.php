<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\LoyaltyAccountResource;
use App\Http\Resources\LoyaltyRewardResource;
use App\Http\Resources\LoyaltyTransactionResource;
use App\Http\Resources\UserRedeemedRewardResource;
use App\Services\LoyaltyService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

final class LoyaltyController extends Controller
{
    public function __construct(
        private readonly LoyaltyService $loyaltyService
    ) {}

    public function account(Request $request): JsonResponse
    {
        $account = $this->loyaltyService->getOrCreateAccount($request->user()->id);

        return response()->json([
            'data' => new LoyaltyAccountResource($account->load(['tier', 'program'])),
        ]);
    }

    public function status(Request $request): JsonResponse
    {
        $status = $this->loyaltyService->getUserStatus($request->user()->id);

        if (! $status) {
            return response()->json(['data' => null]);
        }

        return response()->json(['data' => $status]);
    }

    public function rewards(): AnonymousResourceCollection
    {
        $rewards = $this->loyaltyService->getAvailableRewards();

        return LoyaltyRewardResource::collection($rewards);
    }

    public function redeem(Request $request): JsonResponse
    {
        $request->validate([
            'reward_id' => ['required', 'integer', 'exists:loyalty_rewards,id'],
            'points' => ['nullable', 'integer', 'min:1'],
        ]);

        $redemption = $this->loyaltyService->redeemPoints(
            userId: $request->user()->id,
            rewardId: $request->validated('reward_id'),
            pointsToRedeem: $request->validated('points')
        );

        return (new UserRedeemedRewardResource($redemption->load('reward')))
            ->response()
            ->setStatusCode(201);
    }

    public function redemptions(Request $request): AnonymousResourceCollection
    {
        $redemptions = $this->loyaltyService->getUserRedemptions(
            userId: $request->user()->id,
            perPage: (int) $request->query('per_page', 15)
        );

        return UserRedeemedRewardResource::collection($redemptions);
    }

    public function transactions(Request $request): AnonymousResourceCollection
    {
        $transactions = $this->loyaltyService->getUserTransactions(
            userId: $request->user()->id,
            perPage: (int) $request->query('per_page', 15)
        );

        return LoyaltyTransactionResource::collection($transactions);
    }

    public function validateCode(Request $request): JsonResponse
    {
        $request->validate([
            'code' => ['required', 'string'],
            'order_total' => ['required', 'numeric', 'min:0'],
        ]);

        $result = $this->loyaltyService->validateRewardCode(
            code: $request->validated('code'),
            orderTotal: $request->validated('order_total')
        );

        if (! $result) {
            return response()->json([
                'valid' => false,
                'message' => 'Código inválido o expirado',
            ], 422);
        }

        return response()->json([
            'valid' => true,
            'discount_value' => $result['discount_value'],
            'reward_type' => $result['reward_type'],
            'redemption' => new UserRedeemedRewardResource($result['redemption']),
        ]);
    }

    public function useCode(Request $request): JsonResponse
    {
        $request->validate([
            'code' => ['required', 'string'],
        ]);

        $redemption = $this->loyaltyService->useRewardCode($request->validated('code'));

        return response()->json([
            'data' => new UserRedeemedRewardResource($redemption->load('reward')),
        ]);
    }
}
