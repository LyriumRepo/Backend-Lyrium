<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\EmitInvoiceRequest;
use App\Http\Resources\InvoiceResource;
use App\Models\Invoice;
use App\Services\NubefactService;
use Barryvdh\DomPDF\Facade\Pdf;
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

                $invoice = Invoice::create([
                    'order_id' => $data['order_id'] ?? null,
                    'invoice_number' => Invoice::generateInvoiceNumber(),
                    'document_type' => $data['tipo_de_comprobante'],
                    'series' => $data['serie'],
                    'number' => $data['numero'],
                    'nit' => $data['cliente_numero_de_documento'],
                    'business_name' => $data['cliente_denominacion'],
                    'customer_document_type' => $data['cliente_tipo_de_documento'],
                    'customer_address' => $data['cliente_direccion'] ?? null,
                    'customer_email' => $data['cliente_email'] ?? null,
                    'provider' => 'nubefact',
                    'total' => $total,
                    'status' => 'DRAFT',
                ]);

                $nubefactData = [
                    'tipo_de_comprobante' => $data['tipo_de_comprobante'],
                    'serie' => $data['serie'],
                    'numero' => $data['numero'],
                    'sunat_transaction' => $data['sunat_transaction'] ?? '1',
                    'cliente_tipo_de_documento' => $data['cliente_tipo_de_documento'],
                    'cliente_numero_de_documento' => $data['cliente_numero_de_documento'],
                    'cliente_denominacion' => $data['cliente_denominacion'],
                    'cliente_direccion' => $data['cliente_direccion'] ?? '',
                    'cliente_email' => $data['cliente_email'] ?? '',
                    'fecha_de_emision' => $data['fecha_de_emision'] ?? now('America/Lima')->subDay()->format('d-m-Y'),
                    'moneda' => $data['moneda'] ?? '1',
                    'total_gravada' => $data['total_gravada'],
                    'total_igv' => $data['total_igv'],
                    'total' => $data['total'],
                    'observaciones' => $data['observaciones'] ?? '',
                    'items' => $data['items'],
                ];

                $nubefactResponse = $this->nubefact->emitInvoice($nubefactData);

                $storeCommissions = null;
                if ($orderId = $data['order_id'] ?? null) {
                    $order = \App\Models\Order::with('items.store')->find($orderId);
                    if ($order) {
                        $storeCommissions = $order->items->groupBy('store_id')->map(fn ($items) => [
                            'storeId' => (string) $items->first()->store_id,
                            'storeName' => $items->first()->store?->trade_name ?? $items->first()->store?->store_name ?? '—',
                            'storeSlug' => $items->first()->store?->slug ?? '',
                            'subtotal' => round($items->sum('line_total'), 2),
                            'commissionRate' => (float) ($items->first()->commission_rate ?? 0),
                            'commissionAmount' => round($items->sum('commission_amount'), 2),
                            'commissionIgv' => round($items->sum('commission_amount') * 0.18 / 1.18, 2),
                            'commissionTotal' => round($items->sum('commission_amount'), 2),
                        ])->values()->toArray();
                    }
                }

                $invoice->update([
                    'status' => $nubefactResponse['status'],
                    'provider_invoice_id' => $nubefactResponse['provider_invoice_id'],
                    'authorization_code' => $nubefactResponse['authorization_code'],
                    'qr_data' => $nubefactResponse['qr_data'],
                    'pdf_url' => url('/api/invoices/'.$invoice->id.'/pdf'),
                    'nubefact_response' => $nubefactResponse['raw'],
                    'items' => $data['items'],
                    'store_commissions' => $storeCommissions,
                ]);

                return $invoice->fresh();
            });

            return $this->created(
                new InvoiceResource($result),
                'Comprobante emitido exitosamente.'
            );

        } catch (\RuntimeException $e) {
            Log::error('Nubefact: emision fallida', [
                'error' => $e->getMessage(),
                'serie' => $data['serie'],
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

    public function exportCsv(Request $request)
    {
        $user = $request->user();
        $query = Invoice::where('provider', 'nubefact')->with(['order.items.store']);
        if (! $user->hasRole('administrator')) {
            $query->whereHas('order', fn ($q) => $q->where('user_id', $user->id));
        }
        $invoices = $query->orderBy('created_at', 'desc')->get();

        $callback = function () use ($invoices) {
            $file = fopen('php://output', 'w');
            fputcsv($file, ['#', 'Tipo', 'Serie', 'Nro', 'Cliente', 'RUC/DNI', 'Monto', 'Estado SUNAT', 'Fecha Emision']);
            foreach ($invoices as $i => $inv) {
                fputcsv($file, [
                    $i + 1,
                    $inv->document_type ?? '—',
                    $inv->series,
                    $inv->number,
                    $inv->business_name ?? $inv->customer_name ?? '—',
                    $inv->nit ?? $inv->customer_ruc ?? '—',
                    number_format($inv->total ?? $inv->amount ?? 0, 2),
                    $inv->status ?? '—',
                    optional($inv->emission_date ?? $inv->created_at)->format('d/m/Y'),
                ]);
            }
            fclose($file);
        };

        return response()->stream($callback, 200, [
            'Content-Type' => 'text/csv; charset=utf-8',
            'Content-Disposition' => 'attachment; filename="reporte-comprobantes.csv"',
        ]);
    }

    public function exportPdf(Request $request)
    {
        $user = $request->user();
        $query = Invoice::where('provider', 'nubefact')->with(['order.items.store']);
        if (! $user->hasRole('administrator')) {
            $query->whereHas('order', fn ($q) => $q->where('user_id', $user->id));
        }
        $invoices = $query->orderBy('created_at', 'desc')->get();

        $pdf = Pdf::loadView('pdf.comprobantes', ['invoices' => $invoices]);

        return $pdf->download('reporte-comprobantes.pdf');
    }
}
