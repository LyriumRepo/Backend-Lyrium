<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Http\Controllers\Api\PlanRequestController;
use App\Models\PlanRequest;
use App\Models\Subscription;
use App\Services\IzipayService;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;

class ProcessPlanAutoRenewals extends Command
{
    protected $signature = 'app:process-plan-auto-renewals';

    protected $description = 'Cobra y renueva automáticamente los planes vencidos hoy que tienen auto_renew activado';

    public function handle(IzipayService $izipay, PlanRequestController $planRequestController): int
    {
        $today = Carbon::today()->toDateString();
        $renewed = 0;
        $failed = 0;

        Subscription::with(['store.owner', 'plan', 'planRequest', 'paymentMethod'])
            ->where('status', 'active')
            ->where('auto_renew', true)
            ->whereDate('ends_at', $today)
            ->get()
            ->each(function (Subscription $subscription) use ($izipay, $planRequestController, &$renewed, &$failed) {
                $store = $subscription->store;
                $owner = $store?->owner;
                $paymentMethod = $subscription->paymentMethod;

                if (! $owner || ! $paymentMethod || ! $paymentMethod->isCardTokenized()) {
                    // Sin tarjeta válida no se puede cobrar en silencio; el
                    // recordatorio de vencimiento (7/3/1 días) ya avisó al
                    // vendedor para que renueve manualmente.
                    $this->line("  ⚠ Suscripción {$subscription->id}: sin tarjeta válida, se omite");

                    return;
                }

                $months = $subscription->planRequest?->months ?? 1;
                $totalAmount = $subscription->planRequest?->total_amount ?? $subscription->plan?->monthly_fee ?? 0;

                $planRequest = PlanRequest::create([
                    'store_id' => $subscription->store_id,
                    'plan_id' => $subscription->plan_id,
                    'current_plan_id' => $subscription->plan_id,
                    'payment_method' => PlanRequest::PAYMENT_METHOD_IZIPAY,
                    'months' => $months,
                    'total_amount' => $totalAmount,
                    'payment_status' => PlanRequest::PAYMENT_STATUS_PENDING,
                    'status' => PlanRequest::STATUS_PENDING,
                    'admin_notes' => 'Renovación automática generada por app:process-plan-auto-renewals',
                ]);

                $result = $izipay->chargeSubscriptionRenewal($planRequest, $paymentMethod->card_token, $owner->email);

                if ($result['success']) {
                    $planRequest->update(['izipay_order_id' => $result['izipay_order_id'] ?? null]);
                    $planRequestController->approvePlanRequest($planRequest, null);
                    $renewed++;
                    $this->line("  ✓ {$owner->email} — plan {$subscription->plan?->name} renovado por {$months} mes(es)");

                    return;
                }

                $planRequest->update([
                    'status' => PlanRequest::STATUS_REJECTED,
                    'payment_status' => PlanRequest::PAYMENT_STATUS_FAILED,
                    'izipay_order_id' => $result['izipay_order_id'] ?? null,
                    'admin_notes' => $result['requires_3ds'] ?? false
                        ? 'Cobro automático requiere autenticación 3DS; no se pudo completar en silencio'
                        : ('Cobro automático falló: '.($result['error'] ?? 'error desconocido')),
                ]);

                $failed++;
                Log::warning('[ProcessPlanAutoRenewals] Cobro automático no completado', [
                    'subscription_id' => $subscription->id,
                    'plan_request_id' => $planRequest->id,
                    'requires_3ds' => $result['requires_3ds'] ?? false,
                ]);
                $this->error("  ✗ {$owner->email}: no se pudo cobrar la renovación automática");
            });

        $this->info("Renovaciones completadas: {$renewed} | Fallidas: {$failed}");

        return self::SUCCESS;
    }
}
