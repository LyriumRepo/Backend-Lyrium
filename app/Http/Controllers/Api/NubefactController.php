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
                    'store_commissions'  => $storeCommissions,
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

        $now = now();
        $currentMonthStart  = $now->copy()->startOfMonth();
        $previousMonthStart = $now->copy()->subMonth()->startOfMonth();
        $previousMonthEnd   = $now->copy()->subMonth()->endOfMonth();

        $currentMonth = (clone $query)
            ->whereBetween('created_at', [$currentMonthStart, $now])
            ->whereIn('sunat_status', ['ACCEPTED', 'SENT_WAIT_CDR'])
            ->sum('total');

        $previousMonth = (clone $query)
            ->whereBetween('created_at', [$previousMonthStart, $previousMonthEnd])
            ->whereIn('sunat_status', ['ACCEPTED', 'SENT_WAIT_CDR'])
            ->sum('total');

        $avgAmount = (clone $query)
            ->whereIn('sunat_status', ['ACCEPTED', 'SENT_WAIT_CDR'])
            ->avg('total');

        $topSellers = (clone $query)
            ->whereIn('sunat_status', ['ACCEPTED', 'SENT_WAIT_CDR'])
            ->join('orders', 'invoices.order_id', '=', 'orders.id')
            ->join('order_items', 'orders.id', '=', 'order_items.order_id')
            ->join('stores', 'order_items.store_id', '=', 'stores.id')
            ->selectRaw('
                stores.id,
                COALESCE(stores.store_name, stores.trade_name) as store_name,
                stores.slug,
                SUM(order_items.line_total) as total_vendido
            ')
            ->groupBy('stores.id', 'stores.store_name', 'stores.slug', 'stores.trade_name')
            ->orderByDesc('total_vendido')
            ->limit(5)
            ->get();

        return $this->success([
            'totalFacturadoMesActual'   => (float) $currentMonth,
            'totalFacturadoMesAnterior' => (float) $previousMonth,
            'porcentajeCrecimiento'     => $previousMonth > 0
                ? round((($currentMonth - $previousMonth) / $previousMonth) * 100, 1)
                : ($currentMonth > 0 ? 100.0 : 0.0),
            'montoPromedio'             => (float) ($avgAmount ?? 0),
            'topSellers'                => $topSellers->map(fn ($s) => [
                'id'           => (string) $s->id,
                'name'         => $s->store_name,
                'slug'         => $s->slug,
                'totalVendido' => (float) $s->total_vendido,
            ]),
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
