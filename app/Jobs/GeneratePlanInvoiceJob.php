<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Models\PlanRequest;
use App\Services\PlanInvoiceService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

final class GeneratePlanInvoiceJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public int $backoff = 60;

    public function __construct(private readonly PlanRequest $planRequest) {}

    public function handle(PlanInvoiceService $service): void
    {
        if (! $this->planRequest->isApproved() || ! $this->planRequest->isPaid()) {
            Log::warning('[GeneratePlanInvoiceJob] PlanRequest no está aprobado y pagado — omitiendo', [
                'plan_request_id' => $this->planRequest->id,
            ]);
            return;
        }

        $service->generateForPlanRequest($this->planRequest);
    }
}
