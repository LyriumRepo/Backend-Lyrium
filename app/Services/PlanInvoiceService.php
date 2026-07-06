<?php

declare(strict_types=1);

namespace App\Services;

use App\Exceptions\NubefactException;
use App\Models\Invoice;
use App\Models\PlanRequest;
use App\Models\Store;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

final class PlanInvoiceService
{
    public const SOURCE = 'plan_subscription';

    public function __construct(
        private readonly NubefactService $nubefact,
    ) {}

    public function generateForPlanRequest(PlanRequest $planRequest): Invoice
    {
        $planRequest->loadMissing(['store.owner', 'plan']);

        $store  = $planRequest->store;
        $plan   = $planRequest->plan;
        $series = config('services.nubefact.series.FACTURA', 'FFF1');

        $total       = (float) $planRequest->total_amount;
        $base        = round($total / 1.18, 2);
        $igv         = round($total - $base, 2);

        $planLabel   = $plan->name ?? 'Plan Lyrium';
        $months      = (int) $planRequest->months;
        $description = "Suscripción {$planLabel} — {$months} " . ($months === 1 ? 'mes' : 'meses');

        $items = [[
            'unidad_de_medida' => 'ZZ',
            'descripcion'      => $description,
            'cantidad'         => '1',
            'valor_unitario'   => $base,
            'precio_unitario'  => $total,
            'subtotal'         => $base,
            'tipo_de_igv'      => '1',
            'igv'              => $igv,
            'total'            => $total,
        ]];

        // Resolver el número y crear el registro dentro de la misma transacción:
        // el lockForUpdate de Invoice::resolveNextNumber solo evita colisiones si
        // el INSERT ocurre antes de liberar el lock (ver Invoice::resolveNextNumber).
        $invoice = DB::transaction(function () use ($store, $planRequest, $series, $total, $base, $igv, $items) {
            return Invoice::create([
                'store_id'          => $store->id,
                'plan_request_id'   => $planRequest->id,
                'source'            => self::SOURCE,
                'invoice_number'    => Invoice::generateInvoiceNumber(),
                'type'              => Invoice::TYPE_FACTURA,
                'document_type'     => '1',
                'series'            => $series,
                'number'            => Invoice::resolveNextNumber($series),
                'customer_ruc'      => $store->ruc ?? $store->tax_id ?? '',
                'customer_name'     => $store->business_name ?? $store->store_name ?? $store->trade_name ?? '',
                'customer_document_type' => '6',
                'customer_email'    => $store->owner?->email,
                'provider'          => 'nubefact',
                'total'             => $total,
                'subtotal_sin_igv'  => $base,
                'igv_amount'        => $igv,
                'sunat_status'      => Invoice::SUNAT_STATUS_DRAFT,
                'status'            => 'DRAFT',
                'items'             => $items,
                'emission_date'     => now(),
            ]);
        });

        $maxAttempts = 10;
        $lastException = null;

        for ($attempt = 1; $attempt <= $maxAttempts; $attempt++) {
            try {
                $result = $this->nubefact->emitInvoice($invoice, $items);

                $invoice->update([
                    'sunat_status'        => $result['sunat_status'],
                    'status'              => $result['sunat_status'],
                    'provider_invoice_id' => $result['id'],
                    'authorization_code'  => $result['authorization_code'],
                    'qr_data'             => $result['qr_data'],
                    'pdf_url'             => $result['pdf_url'],
                    'xml_url'             => $result['xml_url'],
                    'cdr_url'             => $result['cdr_url'],
                    'nubefact_response'   => $result['raw'] ?? null,
                ]);

                $lastException = null;
                break;
            } catch (NubefactException $e) {
                // Número duplicado en Nubefact: incrementar y reintentar
                // (mismo patrón que OrderPaymentService::emitInvoiceForStore).
                if ($e->getNubefactCode() === NubefactException::DUPLICATE_DOCUMENT && $attempt < $maxAttempts) {
                    $nextNum = (int) $invoice->number + 1;
                    $invoice->number = str_pad((string) $nextNum, 4, '0', STR_PAD_LEFT);
                    $invoice->save();

                    Log::warning('[PlanInvoiceService] número duplicado en NubeFact, reintentando', [
                        'invoice_id'      => $invoice->id,
                        'plan_request_id' => $planRequest->id,
                        'nuevo_numero'    => $invoice->number,
                        'attempt'         => $attempt,
                    ]);
                    continue;
                }

                $lastException = $e;
                break;
            } catch (\Throwable $e) {
                $lastException = $e;
                break;
            }
        }

        if ($lastException !== null) {
            $isInfraError = $lastException instanceof NubefactException
                && in_array($lastException->getNubefactCode(), [
                    NubefactException::CONFIG_ERROR,
                    NubefactException::CONNECTION_ERROR,
                ], true);

            $failStatus = $isInfraError ? Invoice::SUNAT_STATUS_DRAFT : Invoice::SUNAT_STATUS_REJECTED;

            Log::error('[PlanInvoiceService] Error al emitir factura de suscripción', [
                'plan_request_id' => $planRequest->id,
                'invoice_id'      => $invoice->id,
                'error'           => $lastException->getMessage(),
            ]);

            $invoice->update([
                'sunat_status' => $failStatus,
                'status'       => $failStatus === Invoice::SUNAT_STATUS_DRAFT ? 'DRAFT' : 'ERROR',
            ]);
        }

        return $invoice->fresh();
    }
}
