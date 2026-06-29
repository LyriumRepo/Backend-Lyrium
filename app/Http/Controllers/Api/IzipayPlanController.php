<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Events\PlanStatusChanged;
use App\Http\Controllers\Controller;
use App\Jobs\GeneratePlanInvoiceJob;
use App\Models\Plan;
use App\Models\PlanRequest;
use App\Models\Store;
use App\Models\Subscription;
use App\Models\User;
use App\Notifications\NewPlanRequestNotification;
use App\Notifications\PlanActivatedNotification;
use App\Services\IzipayService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;

final class IzipayPlanController extends Controller
{
    public function __construct(private readonly IzipayService $izipayService) {}

    /**
     * POST /api/payments/izipay/plan-session
     *
     * Crea una sesión de pago Izipay para un plan de suscripción.
     * Aplica el descuento por duración, guarda el PlanRequest pendiente
     * y devuelve formToken + publicKey para abrir el widget de pago.
     */
    public function createSession(Request $request): JsonResponse
    {
        $data = $request->validate([
            'plan_id'   => ['nullable', 'integer', 'exists:plans,id'],
            'plan_slug' => ['nullable', 'string', 'exists:plans,slug'],
            'months'    => ['required', 'integer', 'min:1', 'max:48'],
        ]);

        $user  = $request->user();
        $store = Store::where('owner_id', $user->id)->first();

        if (! $store) {
            return response()->json(['success' => false, 'message' => 'Debes tener una tienda registrada para suscribirte a un plan.'], 403);
        }

        $pending = PlanRequest::where('store_id', $store->id)
            ->where('status', PlanRequest::STATUS_PENDING)
            ->first();

        if ($pending) {
            $isOurOrder   = str_starts_with((string) ($pending->izipay_order_id ?? ''), 'PLAN-');
            $isStale      = $pending->created_at->diffInMinutes(now()) > 15;
            $canReplace   = is_null($pending->izipay_order_id) || $isOurOrder || $isStale;

            if ($canReplace) {
                $pending->delete();
            } else {
                return response()->json([
                    'success' => false,
                    'message' => 'Tienes un pago en proceso. Espera unos minutos o contacta soporte si el problema persiste.',
                ], 422);
            }
        }

        $plan = isset($data['plan_id'])
            ? Plan::findOrFail($data['plan_id'])
            : Plan::where('slug', $data['plan_slug'])->firstOrFail();
        $months       = (int) $data['months'];
        $discount     = $this->getDiscountPercent($months);
        $base         = (float) $plan->monthly_fee * $months;
        $totalAmount  = round($base * (1 - $discount / 100), 2);

        // ID único de referencia para Izipay (prefijo PLAN para distinguirlo de órdenes)
        $izipayOrderId = 'PLAN-' . $store->id . '-' . time();

        // Crear el PlanRequest en BD con estado pendiente antes de abrir el form
        $planRequest = PlanRequest::create([
            'store_id'        => $store->id,
            'plan_id'         => $plan->id,
            'current_plan_id' => $store->activeSubscription?->plan_id,
            'payment_method'  => PlanRequest::PAYMENT_METHOD_IZIPAY,
            'months'          => $months,
            'total_amount'    => $totalAmount,
            'payment_status'  => PlanRequest::PAYMENT_STATUS_PENDING,
            'izipay_order_id' => $izipayOrderId,
            'status'          => PlanRequest::STATUS_PENDING,
        ]);

        try {
            $session = $this->izipayService->initPlanPayment(
                amountSoles:   $totalAmount,
                izipayOrderId: $izipayOrderId,
                email:         $user->email,
            );
        } catch (\Throwable $e) {
            // Si falla Izipay, eliminamos el PlanRequest para no dejar basura
            $planRequest->delete();
            return response()->json([
                'success' => false,
                'message' => 'No se pudo conectar con el servicio de pago. Intenta más tarde.',
            ], 502);
        }

        // Notificar a los administradores solo si Izipay respondió correctamente
        try {
            $planRequest->load(['store.owner', 'plan']);
            $admins = User::role('administrator')->get();
            if ($admins->isNotEmpty()) {
                Notification::send($admins, new NewPlanRequestNotification($planRequest));
            }
        } catch (\Throwable) {
            // Notificación no crítica
        }

        // En modo simulación (sin credenciales Izipay reales), aprobar automáticamente
        // para que el plan se active sin requerir un webhook real.
        if ($session['mode'] === 'mock') {
            $this->autoApproveMockRequest($planRequest, $user);
        }

        return response()->json([
            'success'          => true,
            'form_token'       => $session['form_token'],
            'public_key'       => $session['public_key'],
            'izipay_order_id'  => $izipayOrderId,
            'plan_request_id'  => $planRequest->id,
            'amount'           => $totalAmount,
            'discount_percent' => $discount,
            'mode'             => $session['mode'],
        ]);
    }

    /**
     * Aprueba un PlanRequest de forma inmediata en modo simulación.
     * Replica la lógica de PlanRequestController::approvePlanRequest().
     */
    private function autoApproveMockRequest(PlanRequest $planRequest, object $user): void
    {
        try {
            $planRequest->update([
                'status'         => PlanRequest::STATUS_APPROVED,
                'payment_status' => PlanRequest::PAYMENT_STATUS_PAID,
                'reviewed_by'    => null,
            ]);

            $endsAt = now()->addMonths($planRequest->months);

            $subscription = Subscription::updateOrCreate(
                ['store_id' => $planRequest->store_id, 'status' => 'active'],
                [
                    'plan_id'   => $planRequest->plan_id,
                    'starts_at' => now(),
                    'ends_at'   => $endsAt,
                    'status'    => 'active',
                ]
            );

            $planRequest->store->update(['commission_rate' => $planRequest->plan->commission_rate]);

            $contract = $planRequest->store->contracts()
                ->whereIn('status', ['ACTIVE', 'PENDING'])
                ->latest()
                ->first();

            if ($contract) {
                $contract->update(['end_date' => $endsAt->toDateString()]);
            }

            $subscription->load('plan');
            try {
                broadcast(new PlanStatusChanged($subscription));
            } catch (\Throwable) {}

            if ($user) {
                $user->notify(new PlanActivatedNotification($planRequest->plan, $subscription));
            }

            GeneratePlanInvoiceJob::dispatch($planRequest)->onQueue('default');
        } catch (\Throwable) {
            // No crítico — el PlanRequest fue creado; el admin puede aprobar manualmente
        }
    }

    /**
     * POST /api/payments/izipay/plan-callback  (auth:sanctum)
     *
     * El SDK Krypton de Izipay entrega clientAnswer + hash al frontend.
     * El frontend llama aquí para confirmar el pago sin depender del webhook externo
     * (que no funciona en localhost). Validamos ownership en lugar de firma HMAC.
     */
    public function planCallback(Request $request): JsonResponse
    {
        $data = $request->validate([
            'client_answer'    => ['required'],  // objeto JS o JSON string
            'order_id'         => ['required', 'string'],
        ]);

        $user  = $request->user();
        $store = Store::where('owner_id', $user->id)->first();

        if (! $store) {
            return response()->json(['success' => false, 'message' => 'Tienda no encontrada'], 403);
        }

        $orderId = $data['order_id'];

        $planRequest = PlanRequest::where('izipay_order_id', $orderId)
            ->where('store_id', $store->id)
            ->whereIn('status', [PlanRequest::STATUS_PENDING])
            ->first();

        if (! $planRequest) {
            // Ya fue aprobado (doble llamada) — igualmente devolvemos éxito
            $approved = PlanRequest::where('izipay_order_id', $orderId)
                ->where('store_id', $store->id)
                ->where('status', PlanRequest::STATUS_APPROVED)
                ->first();
            if ($approved) {
                return response()->json(['success' => true, 'message' => 'Plan ya activo']);
            }
            return response()->json(['success' => false, 'message' => 'Solicitud no encontrada'], 404);
        }

        // Extraer orderStatus del clientAnswer (puede venir como objeto o string JSON)
        $answer = is_array($data['client_answer'])
            ? $data['client_answer']
            : json_decode($data['client_answer'], true);

        $orderStatus = $answer['orderStatus'] ?? '';

        Log::info('[IzipayPlanController] planCallback recibido', [
            'order_id'    => $orderId,
            'orderStatus' => $orderStatus,
            'store_id'    => $store->id,
        ]);

        if (! in_array($orderStatus, ['PAID', 'AUTHORISED', 'CAPTURED', 'ACCEPTED'], true)) {
            return response()->json(['success' => false, 'message' => "Pago con estado: {$orderStatus}"], 422);
        }

        $planRequest->update(['payment_status' => PlanRequest::PAYMENT_STATUS_PAID]);
        $this->approvePlanFromCallback($planRequest, $user);

        return response()->json(['success' => true, 'message' => 'Plan activado correctamente']);
    }

    /**
     * Aprueba un PlanRequest proveniente del callback del frontend (real Izipay).
     */
    private function approvePlanFromCallback(PlanRequest $planRequest, object $user): void
    {
        $planRequest->update([
            'status'      => PlanRequest::STATUS_APPROVED,
            'reviewed_by' => null,
        ]);

        $endsAt = now()->addMonths($planRequest->months);

        $subscription = Subscription::updateOrCreate(
            ['store_id' => $planRequest->store_id, 'status' => 'active'],
            ['plan_id' => $planRequest->plan_id, 'starts_at' => now(), 'ends_at' => $endsAt, 'status' => 'active']
        );

        $planRequest->store->update(['commission_rate' => $planRequest->plan->commission_rate]);

        $contract = $planRequest->store->contracts()
            ->whereIn('status', ['ACTIVE', 'PENDING'])
            ->latest()->first();
        if ($contract) {
            $contract->update(['end_date' => $endsAt->toDateString()]);
        }

        $subscription->load('plan');
        try { broadcast(new PlanStatusChanged($subscription)); } catch (\Throwable) {}

        try { $user->notify(new PlanActivatedNotification($planRequest->plan, $subscription)); } catch (\Throwable) {}

        GeneratePlanInvoiceJob::dispatch($planRequest)->onQueue('default');
    }

    /**
     * Tabla de descuentos por duración — idéntica a getDiscountForMonths() del frontend.
     */
    private function getDiscountPercent(int $months): int
    {
        return match(true) {
            $months <= 1  => 0,
            $months <= 3  => 5,
            $months <= 6  => 12,
            $months <= 12 => 22,
            $months <= 18 => 30,
            $months <= 24 => 38,
            $months <= 36 => 48,
            default       => min(48 + ($months - 36), 60),
        };
    }
}
