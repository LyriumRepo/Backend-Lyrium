<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Invoice;
use App\Models\PlanRequest;
use App\Models\Store;
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

        $invoice = Invoice::create([
            'store_id'          => $store->id,
            'plan_request_id'   => $planRequest->id,
            'source'            => self::SOURCE,
            'invoice_number'    => Invoice::generateInvoiceNumber(),
            'type'              => Invoice::TYPE_FACTURA,
            'document_type'     => '1',
            'series'            => $series,
            'number'            => $this->nextNumber($series),
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
        } catch (\Throwable $e) {
            Log::error('[PlanInvoiceService] Error al emitir factura de suscripción', [
                'plan_request_id' => $planRequest->id,
                'error'           => $e->getMessage(),
            ]);

            $invoice->update([
                'sunat_status' => Invoice::SUNAT_STATUS_REJECTED,
                'status'       => 'ERROR',
            ]);
        }

        return $invoice->fresh();
    }

    private function nextNumber(string $series): string
    {
        // Consultar TODAS las facturas de la serie (no solo plan_subscription)
        // para evitar colisiones con el flujo de facturas de órdenes.
        $last = Invoice::where('series', $series)->max('number');

        $nextNum = $last ? ((int) $last + 1) : 1;

        return str_pad((string) $nextNum, 8, '0', STR_PAD_LEFT);
    }
}
