<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\LiriosService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class LiriosController extends Controller
{
    public function __construct(
        private readonly LiriosService $liriosService,
    ) {}

    /**
     * GET /api/lirios/checkout-eligibility
     * Params: cart_total (float), store_ids (comma-separated IDs, optional)
     */
    public function checkoutEligibility(Request $request): JsonResponse
    {
        $request->validate([
            'cart_total' => 'required|numeric|min:0',
            'store_ids' => 'nullable|string',
        ]);

        $user = $request->user();
        $cartTotal = (float) $request->input('cart_total');
        $storeIds = [];

        if ($request->filled('store_ids')) {
            $storeIds = explode(',', $request->input('store_ids'));
            $storeIds = array_map('intval', $storeIds);
        }

        $result = $this->liriosService->checkoutEligibility(
            $user->id,
            $cartTotal,
            $storeIds,
        );

        return $this->success($result);
    }

    /**
     * GET /api/lirios/balance
     */
    public function balance(Request $request): JsonResponse
    {
        $user = $request->user();
        $balance = $this->liriosService->getBalance($user->id);

        return $this->success([
            'balance' => $balance,
        ]);
    }

    /**
     * GET /api/lirios/transactions
     */
    public function transactions(Request $request): JsonResponse
    {
        $user = $request->user();

        $transactions = \App\Models\LiriosTransaction::where('user_id', $user->id)
            ->orderBy('created_at', 'desc')
            ->paginate(15);

        return $this->success([
            'data' => $transactions,
            'pagination' => [
                'page' => $transactions->currentPage(),
                'perPage' => $transactions->perPage(),
                'total' => $transactions->total(),
                'totalPages' => $transactions->lastPage(),
                'hasMore' => $transactions->hasMorePages(),
            ],
        ]);
    }

    /**
     * POST /api/lirios/accrue
     * Body: order_id (int)
     * Solo para uso interno/testing o llamado desde el webhook de pago.
     */
    public function accrue(Request $request): JsonResponse
    {
        $request->validate([
            'order_id' => 'required|integer|exists:orders,id',
        ]);

        $user = $request->user();
        $order = \App\Models\Order::findOrFail($request->input('order_id'));

        if ($order->user_id !== $user->id) {
            return $this->forbidden('Esta orden no te pertenece.');
        }

        if ($order->payment_status !== 'paid') {
            return $this->error('La orden aún no ha sido pagada.', 400);
        }

        try {
            $totalPaid = $order->total;
            $transaction = $this->liriosService->accrue($user->id, $totalPaid, $order);
        } catch (\RuntimeException $e) {
            return $this->error($e->getMessage(), 400);
        }

        return $this->success([
            'transaction' => $transaction,
            'new_balance' => $transaction->balance_after,
        ]);
    }

    /**
     * GET /api/lirios/admin/accounts
     * Admin: listar cuentas Lirios
     */
    public function adminAccounts(Request $request): JsonResponse
    {
        $accounts = \App\Models\LiriosAccount::with('user')
            ->orderBy('balance', 'desc')
            ->paginate(20);

        return $this->success([
            'data' => $accounts,
            'pagination' => [
                'page' => $accounts->currentPage(),
                'perPage' => $accounts->perPage(),
                'total' => $accounts->total(),
                'totalPages' => $accounts->lastPage(),
                'hasMore' => $accounts->hasMorePages(),
            ],
        ]);
    }

    /**
     * PUT /api/lirios/admin/accounts/{userId}
     * Admin: ajustar balance manualmente
     */
    public function adminUpdateBalance(Request $request, int $userId): JsonResponse
    {
        $request->validate([
            'balance' => 'required|integer|min:0',
        ]);

        $account = \App\Models\LiriosAccount::firstOrCreate(
            ['user_id' => $userId],
            ['balance' => 0],
        );

        $account->update(['balance' => $request->input('balance')]);

        return $this->success($account);
    }
}
