<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Plan;
use App\Models\PlanRequest;
use App\Models\Store;
use App\Models\Subscription;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class PlanRequestController extends Controller
{
    public function store(Request $request): JsonResponse
    {
        $user = $request->user();
        $store = Store::where('owner_id', $user->id)->first();

        if (! $store) {
            return response()->json(['message' => 'No tienes una tienda registrada'], 404);
        }

        $data = $request->validate([
            'plan_id' => ['required', 'integer', 'exists:plans,id'],
            'payment_method' => ['required', 'in:trial,izipay'],
            'months' => ['required', 'integer', 'min:1', 'max:48'],
            'izipay_order_id' => ['nullable', 'string', 'max:100'],
        ]);

        $plan = Plan::findOrFail($data['plan_id']);
        $currentSubscription = Subscription::query()
            ->where('store_id', $store->id)
            ->where('status', 'active')
            ->where('ends_at', '>=', now())
            ->first();

        $currentPlanId = $currentSubscription?->plan_id;

        $pendingRequest = PlanRequest::where('store_id', $store->id)
            ->where('status', PlanRequest::STATUS_PENDING)
            ->first();

        if ($pendingRequest) {
            return response()->json([
                'message' => 'Ya tienes una solicitud de plan pendiente',
                'request' => [
                    'id' => $pendingRequest->id,
                    'status' => $pendingRequest->status,
                    'plan_name' => $pendingRequest->plan->name,
                ],
            ], 422);
        }

        $months = $data['months'];
        $monthlyFee = (float) $plan->monthly_fee;
        $totalAmount = $monthlyFee * $months;

        $planRequest = PlanRequest::create([
            'store_id' => $store->id,
            'plan_id' => $data['plan_id'],
            'current_plan_id' => $currentPlanId,
            'payment_method' => $data['payment_method'],
            'months' => $months,
            'total_amount' => $totalAmount,
            'payment_status' => $data['payment_method'] === PlanRequest::PAYMENT_METHOD_TRIAL
                ? PlanRequest::PAYMENT_STATUS_PAID
                : PlanRequest::PAYMENT_STATUS_PENDING,
            'izipay_order_id' => $data['izipay_order_id'] ?? null,
            'status' => PlanRequest::STATUS_PENDING,
        ]);

        if ($planRequest->canAutoApprove()) {
            $this->approvePlanRequest($planRequest, null);
        }

        return response()->json([
            'message' => $planRequest->isApproved()
                ? 'Plan activado correctamente'
                : 'Solicitud enviada para revisión',
            'request' => [
                'id' => $planRequest->id,
                'status' => $planRequest->status,
                'plan_name' => $plan->name,
                'payment_method' => $planRequest->payment_method,
                'created_at' => $planRequest->created_at->toIso8601String(),
            ],
        ], $planRequest->isApproved() ? 200 : 201);
    }

    public function me(Request $request): JsonResponse
    {
        $user = $request->user();
        $store = Store::where('owner_id', $user->id)->first();

        if (! $store) {
            return response()->json(['message' => 'No tienes una tienda registrada'], 404);
        }

        $latestRequest = PlanRequest::where('store_id', $store->id)
            ->with('plan:id,name,slug,monthly_fee')
            ->orderBy('created_at', 'desc')
            ->first();

        if (! $latestRequest) {
            return response()->json([
                'has_active_request' => false,
                'current_plan' => $this->getCurrentPlanInfo($store),
            ]);
        }

        return response()->json([
            'has_active_request' => $latestRequest->isPending(),
            'request' => $latestRequest->isPending() ? [
                'id' => $latestRequest->id,
                'status' => $latestRequest->status,
                'payment_status' => $latestRequest->payment_status,
                'plan' => [
                    'id' => $latestRequest->plan->id,
                    'name' => $latestRequest->plan->name,
                    'monthly_fee' => $latestRequest->plan->monthly_fee,
                ],
                'created_at' => $latestRequest->created_at->toIso8601String(),
            ] : null,
            'current_plan' => $this->getCurrentPlanInfo($store),
        ]);
    }

    public function index(Request $request): JsonResponse
    {
        $query = PlanRequest::query()
            ->with(['store.owner:id,name,email', 'plan:id,name,monthly_fee'])
            ->orderBy('created_at', 'desc');

        if ($status = $request->query('status')) {
            $query->where('status', $status);
        }

        if ($paymentStatus = $request->query('payment_status')) {
            $query->where('payment_status', $paymentStatus);
        }

        $requests = $query->paginate($request->query('per_page', 20));

        return response()->json([
            'data' => $requests->map(fn ($req) => [
                'id' => $req->id,
                'store_id' => $req->store_id,
                'store_name' => $req->store->trade_name,
                'seller_name' => $req->store->owner?->name,
                'seller_email' => $req->store->owner?->email,
                'plan' => [
                    'id' => $req->plan->id,
                    'name' => $req->plan->name,
                    'monthly_fee' => $req->plan->monthly_fee,
                ],
                'months' => $req->months,
                'total_amount' => $req->total_amount,
                'payment_method' => $req->payment_method,
                'payment_status' => $req->payment_status,
                'status' => $req->status,
                'created_at' => $req->created_at->toIso8601String(),
            ]),
            'pagination' => [
                'page' => $requests->currentPage(),
                'perPage' => $requests->perPage(),
                'total' => $requests->total(),
                'totalPages' => $requests->lastPage(),
                'hasMore' => $requests->hasMorePages(),
            ],
        ]);
    }

    public function show(int $id): JsonResponse
    {
        $req = PlanRequest::with([
            'store.owner:id,name,email',
            'store' => fn ($q) => $q->select('id', 'trade_name', 'ruc', 'owner_id'),
            'plan:id,name,slug,monthly_fee,commission_rate',
            'currentPlan:id,name',
            'reviewer:id,name',
        ])->findOrFail($id);

        return response()->json([
            'id' => $req->id,
            'store_id' => $req->store_id,
            'store_name' => $req->store->trade_name,
            'store_ruc' => $req->store->ruc,
            'seller_name' => $req->store->owner?->name,
            'seller_email' => $req->store->owner?->email,
            'current_plan' => $req->currentPlan ? [
                'id' => $req->currentPlan->id,
                'name' => $req->currentPlan->name,
            ] : null,
            'requested_plan' => [
                'id' => $req->plan->id,
                'name' => $req->plan->name,
                'monthly_fee' => $req->plan->monthly_fee,
                'commission_rate' => $req->plan->commission_rate,
            ],
            'payment_method' => $req->payment_method,
            'payment_status' => $req->payment_status,
            'izipay_order_id' => $req->izipay_order_id,
            'status' => $req->status,
            'admin_notes' => $req->admin_notes,
            'reviewed_by' => $req->reviewer?->name,
            'created_at' => $req->created_at->toIso8601String(),
            'updated_at' => $req->updated_at->toIso8601String(),
        ]);
    }

    public function approve(Request $request, int $id): JsonResponse
    {
        $planRequest = PlanRequest::with('store')->findOrFail($id);

        if ($planRequest->status !== PlanRequest::STATUS_PENDING) {
            return response()->json([
                'message' => 'Esta solicitud ya ha sido procesada',
            ], 422);
        }

        $this->approvePlanRequest($planRequest, $request->user()->id);

        return response()->json([
            'message' => 'Plan activado correctamente',
            'request' => [
                'id' => $planRequest->id,
                'status' => $planRequest->status,
                'reviewed_at' => $planRequest->updated_at->toIso8601String(),
            ],
        ]);
    }

    public function reject(Request $request, int $id): JsonResponse
    {
        $planRequest = PlanRequest::with('store')->findOrFail($id);

        if ($planRequest->status !== PlanRequest::STATUS_PENDING) {
            return response()->json([
                'message' => 'Esta solicitud ya ha sido procesada',
            ], 422);
        }

        $request->validate([
            'notes' => ['required', 'string', 'min:10', 'max:1000'],
        ]);

        $planRequest->update([
            'status' => PlanRequest::STATUS_REJECTED,
            'admin_notes' => $request->input('notes'),
            'reviewed_by' => $request->user()->id,
        ]);

        return response()->json([
            'message' => 'Solicitud rechazada',
            'request' => [
                'id' => $planRequest->id,
                'status' => $planRequest->status,
                'admin_notes' => $planRequest->admin_notes,
                'reviewed_at' => $planRequest->updated_at->toIso8601String(),
            ],
        ]);
    }

    public function stream(Request $request): \Symfony\Component\HttpFoundation\StreamedResponse
    {
        $lastEventId = (int) $request->header('Last-Event-ID', 0);

        return response()->stream(function () use ($lastEventId) {
            $lastId = $lastEventId;

            while (true) {
                $newRequests = PlanRequest::query()
                    ->with(['store.owner:id,name,email', 'plan:id,name'])
                    ->where('id', '>', $lastId)
                    ->where('status', PlanRequest::STATUS_PENDING)
                    ->orderBy('id', 'asc')
                    ->limit(5)
                    ->get();

                foreach ($newRequests as $req) {
                    $lastId = $req->id;

                    $data = json_encode([
                        'id' => $req->id,
                        'type' => 'new_plan_request',
                        'store_name' => $req->store->trade_name,
                        'seller_name' => $req->store->owner?->name,
                        'plan_name' => $req->plan->name,
                        'payment_method' => $req->payment_method,
                        'created_at' => $req->created_at->toIso8601String(),
                    ]);

                    echo "id: {$req->id}\n";
                    echo "event: new_plan_request\n";
                    echo "data: {$data}\n\n";
                    ob_flush();
                    flush();
                }

                if ($newRequests->isNotEmpty()) {
                    $pendingCount = PlanRequest::where('status', PlanRequest::STATUS_PENDING)->count();
                    $countData = json_encode(['pending_count' => $pendingCount]);
                    echo "event: pending_count\n";
                    echo "data: {$countData}\n\n";
                    ob_flush();
                    flush();
                }

                sleep(3);
            }
        }, 200, [
            'Content-Type' => 'text/event-stream',
            'Cache-Control' => 'no-cache',
            'Connection' => 'keep-alive',
        ]);
    }

    public function webhookIzipay(Request $request): JsonResponse
    {
        $data = $request->all();

        $orderId = $data['orderId'] ?? $data['order_id'] ?? null;

        if (! $orderId) {
            return response()->json(['message' => 'Order ID no proporcionado'], 400);
        }

        $planRequest = PlanRequest::where('izipay_order_id', $orderId)
            ->where('status', PlanRequest::STATUS_PENDING)
            ->first();

        if (! $planRequest) {
            return response()->json(['message' => 'Solicitud no encontrada'], 404);
        }

        $transactionState = $data['transactionState'] ?? $data['transaction_state'] ?? '';

        if ($transactionState === 'AUTHORIZED' || $transactionState === 'CAPTURED') {
            $planRequest->update([
                'payment_status' => PlanRequest::PAYMENT_STATUS_PAID,
            ]);

            $this->approvePlanRequest($planRequest, null);

            return response()->json([
                'success' => true,
                'message' => 'Pago confirmado y plan activado',
            ]);
        }

        if ($transactionState === 'FAILED' || $transactionState === 'EXPIRED') {
            $planRequest->update([
                'payment_status' => PlanRequest::PAYMENT_STATUS_FAILED,
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Pago fallido',
            ]);
        }

        return response()->json([
            'success' => true,
            'message' => 'Estado de pago actualizado',
        ]);
    }

    private function approvePlanRequest(PlanRequest $planRequest, ?int $reviewedBy): void
    {
        $planRequest->update([
            'status' => PlanRequest::STATUS_APPROVED,
            'reviewed_by' => $reviewedBy,
        ]);

        $endsAt = now()->addMonths($planRequest->months);

        Subscription::updateOrCreate(
            [
                'store_id' => $planRequest->store_id,
                'status' => 'active',
            ],
            [
                'plan_id' => $planRequest->plan_id,
                'starts_at' => now(),
                'ends_at' => $endsAt,
                'status' => 'active',
            ]
        );

        $planRequest->store->update([
            'commission_rate' => $planRequest->plan->commission_rate,
        ]);

        // Actualizar end_date del contrato activo/pendiente con la vigencia de la suscripción
        $contract = $planRequest->store->contracts()
            ->whereIn('status', ['ACTIVE', 'PENDING'])
            ->latest()
            ->first();

        if ($contract) {
            $contract->update(['end_date' => $endsAt->toDateString()]);
            $contract->addAuditEntry(
                "Vigencia del contrato actualizada por activación del plan {$planRequest->plan->name}",
                'Sistema'
            );
        }
    }

    private function getCurrentPlanInfo(Store $store): ?array
    {
        $subscription = Subscription::query()
            ->where('store_id', $store->id)
            ->where('status', 'active')
            ->where('ends_at', '>=', now())
            ->with('plan:id,name,monthly_fee')
            ->first();

        if (! $subscription) {
            return null;
        }

        return [
            'id' => $subscription->plan->id,
            'name' => $subscription->plan->name,
            'monthly_fee' => $subscription->plan->monthly_fee,
            'ends_at' => $subscription->ends_at->toIso8601String(),
        ];
    }
}
