<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Plan;
use App\Models\PlanRequest;
use App\Models\Store;
use App\Services\IzipayService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class IzipayPlanController extends Controller
{
    public function __construct(private readonly IzipayService $izipayService) {}

    /**
     * POST /api/payments/izipay/plan-session
     *
     * Crea una sesión de pago Izipay para un plan de suscripción.
     * Devuelve el formToken necesario para abrir el widget de pago.
     */
    public function createSession(Request $request): JsonResponse
    {
        $data = $request->validate([
            'plan_id' => ['required', 'integer', 'exists:plans,id'],
            'months'  => ['required', 'integer', 'min:1', 'max:48'],
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
            return response()->json([
                'success' => false,
                'message' => 'Ya tienes una solicitud de plan pendiente.',
            ], 422);
        }

        $plan        = Plan::findOrFail($data['plan_id']);
        $months      = (int) $data['months'];
        $totalAmount = round((float) $plan->monthly_fee * $months, 2);

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
                amountSoles:     $totalAmount,
                izipayOrderId:   $izipayOrderId,
                email:           $user->email,
            );
        } catch (\Throwable $e) {
            // Si falla Izipay, eliminamos el PlanRequest para no dejar basura
            $planRequest->delete();
            return response()->json([
                'success' => false,
                'message' => 'No se pudo conectar con el servicio de pago. Intenta más tarde.',
            ], 502);
        }

        return response()->json([
            'success'          => true,
            'form_token'       => $session['form_token'],
            'public_key'       => $session['public_key'],
            'izipay_order_id'  => $izipayOrderId,
            'plan_request_id'  => $planRequest->id,
            'amount'           => $totalAmount,
            'mode'             => $session['mode'],
        ]);
    }
}
