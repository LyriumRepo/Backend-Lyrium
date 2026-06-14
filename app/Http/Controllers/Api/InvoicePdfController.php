<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Invoice;
use App\Services\CommissionService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;

final class InvoicePdfController extends Controller
{
    public function show(Request $request, string $id)
    {
        $invoice = Invoice::with(['order.items.store', 'order.user'])->findOrFail($id);

        $user = $request->user();
        if ($user) {
            if (
                ! $user->hasRole('administrator') &&
                ! $user->hasRole('seller') &&
                $invoice->order?->user_id !== $user->id
            ) {
                return $this->forbidden('No tienes acceso a esta factura.');
            }
        }

        $order = $invoice->order;
        $items = $invoice->items;
        if (empty($items) && $order) {
            $items = $order->items->map(fn ($oi) => [
                'cantidad' => $oi->quantity,
                'descripcion' => $oi->product_name,
                'valor_unitario' => round($oi->unit_price / 1.18, 2),
                'precio_unitario' => $oi->unit_price,
                'descuento' => 0,
                'total' => $oi->line_total,
                'subtotal' => $oi->line_total,
            ])->toArray();
        }

        $documentTypes = [
            '1' => 'FACTURA ELECTRÓNICA',
            '2' => 'BOLETA ELECTRÓNICA',
            '3' => 'NOTA DE CRÉDITO',
            '4' => 'NOTA DE DÉBITO',
        ];

        $totalGravada = 0;
        $formattedItems = [];
        foreach ($items as $item) {
            $cantidad = (float) ($item['cantidad'] ?? 1);
            $valorUnitario = (float) ($item['valor_unitario'] ?? 0);
            $precioUnitario = (float) ($item['precio_unitario'] ?? 0);
            $subtotal = (float) ($item['subtotal'] ?? 0);
            $total = (float) ($item['total'] ?? $subtotal);
            $descuento = (float) ($item['descuento'] ?? 0);

            $totalGravada += $total;

            $formattedItems[] = [
                'cantidad' => $cantidad,
                'descripcion' => $item['descripcion'] ?? '—',
                'valor_unitario' => $valorUnitario,
                'precio_unitario' => $precioUnitario,
                'descuento' => $descuento,
                'total' => $total,
            ];
        }

        $shippingCost = (float) ($order?->shipping_cost ?? 0);

        $totalConEnvio = $totalGravada + $shippingCost;
        $invoiceTotal = (float) $invoice->total;
        $igv = round($totalGravada * 0.18, 2);
        $total = $invoiceTotal > 0 ? $invoiceTotal : ($totalGravada + $igv + $shippingCost);

        $showCommission = true;
        $commissionBase = round($totalConEnvio / 1.18, 2);

        $commissionSummary = [];
        if ($order) {
            try {
                $commissionSummary = app(CommissionService::class)->getCommissionSummary($order);
            } catch (\Throwable $e) {
                $commissionSummary = [];
            }
        }

        $hasCommissionData = ! empty($commissionSummary);
        $commissionAmount = $hasCommissionData ? $commissionSummary['total_commission'] : 0;
        $commissionIgv = $hasCommissionData ? $commissionSummary['commission_igv'] : 0;
        $commissionTotal = $hasCommissionData ? $commissionSummary['commission_total'] : 0;
        $commissionRate = $hasCommissionData && ! empty($commissionSummary['items']) && $commissionSummary['items']->isNotEmpty()
            ? $commissionSummary['items']->first()['commission_rate']
            : 15.0;

        $data = [
            'companyName' => 'LYRIUM EIRL',
            'companyRuc' => '20612731838',
            'companyAddress' => 'MZ.KD LT.5 URB. SANTA MARGARITA, Villa María del Triunfo – Lima',
            'companyEmail' => 'contacto@lyrium.pe',
            'companyPhone' => '+51 999 888 777',
            'documentTypeLabel' => $documentTypes[$invoice->document_type] ?? 'COMPROBANTE ELECTRÓNICO',
            'series' => $invoice->series,
            'number' => $invoice->number,
            'emissionDate' => $invoice->created_at?->locale('es')->isoFormat('DD/MM/YYYY') ?? now()->format('d/m/Y'),
            'customerName' => $invoice->business_name ?? $order?->shipping_name ?? '—',
            'customerDocType' => match ($invoice->customer_document_type) {
                '6' => 'RUC',
                '1' => 'DNI',
                '4' => 'CE',
                default => $invoice->customer_document_type ?? '—',
            },
            'customerDocNumber' => $invoice->nit ?? '—',
            'customerAddress' => $invoice->customer_address ?? $order?->shipping_address ?? '—',
            'customerEmail' => $invoice->customer_email ?? $order?->shipping_email ?? null,
            'items' => $formattedItems,
            'totalGravada' => $totalGravada,
            'shippingCost' => $shippingCost,
            'igv' => $igv,
            'total' => $total,
            'showCommission' => $showCommission,
            'commissionBase' => $commissionBase,
            'commissionAmount' => $commissionAmount,
            'commissionIgv' => $commissionIgv,
            'commissionTotal' => $commissionTotal,
            'commissionRate' => $commissionRate,
            'amountInWords' => $this->numberToWords($total),
            'authorizationCode' => $invoice->authorization_code ?? '—',
            'generatedAt' => now()->locale('es')->isoFormat('DD/MM/YYYY HH:mm:ss'),
        ];

        $pdf = Pdf::loadView('pdf.invoice', $data);
        $pdf->setPaper('A4', 'portrait');

        return $pdf->stream("factura-{$invoice->series}-{$invoice->number}.pdf");
    }

    private function numberToWords(float $number): string
    {
        $entero = floor($number);
        $decimal = round(($number - $entero) * 100);

        $formatter = new \NumberFormatter('es', \NumberFormatter::SPELLOUT);
        $words = ucfirst($formatter->format($entero));

        return "{$words} {$decimal}/100 SOLES";
    }
}
