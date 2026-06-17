<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\EmitInvoiceRequest;
use App\Http\Resources\InvoiceResource;
use App\Models\Invoice;
use App\Services\NubefactService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

final class NubefactController extends Controller
{
    public function __construct(
        private readonly NubefactService $nubefact,
    ) {}

    public function emitir(EmitInvoiceRequest $request): JsonResponse
    {
        $data = $request->validated();

        try {
            $result = DB::transaction(function () use ($data) {
                $total = (float) $data['total'];

                $typeMap = [
                    '1' => Invoice::TYPE_FACTURA,
                    '2' => Invoice::TYPE_BOLETA,
                    '3' => Invoice::TYPE_NOTA_CREDITO,
                ];

                $invoice = Invoice::create([
                    'order_id'               => $data['order_id'] ?? null,
                    'invoice_number'         => Invoice::generateInvoiceNumber(),
                    'type'                   => $typeMap[$data['tipo_de_comprobante']] ?? Invoice::TYPE_FACTURA,
                    'document_type'          => $data['tipo_de_comprobante'],
                    'series'                 => $data['serie'],
                    'number'                 => $data['numero'],
                    'customer_ruc'           => $data['cliente_numero_de_documento'],
                    'customer_name'          => $data['cliente_denominacion'],
                    'customer_document_type' => $data['cliente_tipo_de_documento'],
                    'customer_address'       => $data['cliente_direccion'] ?? null,
                    'customer_email'         => $data['cliente_email'] ?? null,
                    'provider'               => 'nubefact',
                    'total'                  => $total,
                    'subtotal_sin_igv'       => (float) $data['total_gravada'],
                    'igv_amount'             => (float) $data['total_igv'],
                    'sunat_status'           => Invoice::SUNAT_STATUS_DRAFT,
                    'status'                 => 'DRAFT',
                ]);

                // emitInvoice espera Invoice model + items explícitos
                $nubefactResponse = $this->nubefact->emitInvoice($invoice, $data['items']);

                $invoice->update([
                    'sunat_status'       => $nubefactResponse['sunat_status'],
                    'status'             => $nubefactResponse['sunat_status'],
                    'provider_invoice_id'=> $nubefactResponse['id'],
                    'authorization_code' => $nubefactResponse['authorization_code'],
                    'qr_data'            => $nubefactResponse['qr_data'],
                    'pdf_url'            => $nubefactResponse['pdf_url'],
                    'xml_url'            => $nubefactResponse['xml_url'],
                    'cdr_url'            => $nubefactResponse['cdr_url'],
                    'nubefact_response'  => $nubefactResponse['raw'],
                    'items'              => $data['items'],
                ]);

                return $invoice->fresh();
            });

            return $this->created(
                new InvoiceResource($result),
                'Comprobante emitido exitosamente.'
            );

        } catch (\Throwable $e) {
            Log::error('Nubefact: emision fallida', [
                'error'  => $e->getMessage(),
                'serie'  => $data['serie'],
                'numero' => $data['numero'],
            ]);

            return $this->error('Error al emitir comprobante: '.$e->getMessage(), 502);
        }
    }

    public function listar(Request $request): JsonResponse
    {
        $user = $request->user();

        $query = Invoice::where('provider', 'nubefact')->with(['order.items.store']);

        if (! $user->hasRole('administrator')) {
            $query->whereHas('order', fn ($q) => $q->where('user_id', $user->id));
        }

        $perPage = (int) $request->input('per_page', 20);
        $invoices = $query->orderBy('created_at', 'desc')->paginate(min($perPage, 100));

        return $this->success([
            'data' => InvoiceResource::collection($invoices),
            'pagination' => [
                'page' => $invoices->currentPage(),
                'perPage' => $invoices->perPage(),
                'total' => $invoices->total(),
                'totalPages' => $invoices->lastPage(),
                'hasMore' => $invoices->hasMorePages(),
            ],
        ]);
    }

    public function mostrar(Request $request, string $id): JsonResponse
    {
        $user = $request->user();
        $invoice = Invoice::where('provider', 'nubefact')->with(['order.items.store'])->findOrFail($id);

        if (! $user->hasRole('administrator') && $invoice->order?->user_id !== $user->id) {
            return $this->forbidden('No tienes acceso a este comprobante.');
        }

        return $this->success(new InvoiceResource($invoice));
    }

    public function kpis(Request $request): JsonResponse
    {
        $user = $request->user();

        $query = Invoice::where('provider', 'nubefact');

        if (! $user->hasRole('administrator')) {
            $query->whereHas('order', fn ($q) => $q->where('user_id', $user->id));
        }

        $kpis = (clone $query)
            ->selectRaw('
                COALESCE(SUM(CASE WHEN status IN ("ACCEPTED","SENT_WAIT_CDR") THEN total ELSE 0 END), 0) as total_facturado,
                COUNT(*) as total_comprobantes,
                COALESCE(SUM(CASE WHEN status = "SENT_WAIT_CDR" THEN 1 ELSE 0 END), 0) as pendientes_cdr,
                COALESCE(SUM(CASE WHEN status IN ("REJECTED","OBSERVED") THEN 1 ELSE 0 END), 0) as rechazados_observados,
                COALESCE(SUM(CASE WHEN status = "ACCEPTED" THEN 1 ELSE 0 END), 0) as aceptados
            ')
            ->first();

        return $this->success([
            'totalFacturado' => (float) ($kpis->total_facturado ?? 0),
            'totalComprobantes' => (int) ($kpis->total_comprobantes ?? 0),
            'pendientesCdr' => (int) ($kpis->pendientes_cdr ?? 0),
            'rechazadosObservados' => (int) ($kpis->rechazados_observados ?? 0),
            'aceptados' => (int) ($kpis->aceptados ?? 0),
        ]);
    }
}
