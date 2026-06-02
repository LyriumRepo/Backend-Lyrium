<?php

declare(strict_types=1);

namespace App\Services;

use App\Contracts\InvoiceProviderInterface;
use App\Exceptions\NubefactException;
use App\Models\Invoice;
use App\Models\Order;
use App\Models\SellerPayment;
use App\Models\Store;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

final class OrderPaymentService
{
    private const IGV_RATE = 0.18;

    public function __construct(
        private readonly NubefactService $nubefact,
        private readonly InvoiceProviderInterface $provider,
        private readonly PaymentSchedulerService $paymentScheduler,
    ) {}

    public static function fromConfig(): self
    {
        return new self(
            nubefact: NubefactService::fromConfig(),
            provider: \App\Services\NubefactProvider::fromConfig(),
            paymentScheduler: new PaymentSchedulerService(),
        );
    }

    /**
     * Procesa el pago exitoso de una orden:
     * 1. Actualiza payment_status a 'paid'
     * 2. Divide items por tienda
     * 3. Genera y emite factura independiente para cada tienda via NubeFact
     * 4. Crea SellerPayment con comisión correcta (sobre subtotal sin IGV)
     */
    public function processSuccessfulPayment(Order $order, string $paymentMethod = 'izipay'): void
    {
        DB::transaction(function () use ($order, $paymentMethod) {
            $order->update([
                'payment_status' => Order::PAYMENT_STATUS_PAID,
                'payment_method' => $paymentMethod,
            ]);

            $order->load(['items.product.store', 'user']);

            $storesInvolved = $order->items->pluck('store_id')->unique();

            foreach ($storesInvolved as $storeId) {
                $store = Store::find($storeId);
                if (! $store) {
                    Log::warning('OrderPaymentService: store not found', ['store_id' => $storeId, 'order_id' => $order->id]);
                    continue;
                }

                $storeItems = $order->items->where('store_id', $storeId);
                $subtotalSinIgv = (float) $storeItems->sum('line_total');

                if ($subtotalSinIgv <= 0) {
                    continue;
                }

                try {
                    $this->emitInvoiceForStore($order, $store, $storeItems, $subtotalSinIgv);
                } catch (\Throwable $e) {
                    Log::error('OrderPaymentService: error al emitir factura para tienda', [
                        'store_id' => $storeId,
                        'order_id' => $order->id,
                        'error' => $e->getMessage(),
                    ]);
                }

                try {
                    $this->scheduleCommissionForStore($order, $store, $subtotalSinIgv);
                } catch (\Throwable $e) {
                    Log::error('OrderPaymentService: error al programar comisión para tienda', [
                        'store_id' => $storeId,
                        'order_id' => $order->id,
                        'error' => $e->getMessage(),
                    ]);
                }
            }
        });
    }

    private function emitInvoiceForStore(Order $order, Store $store, iterable $storeItems, float $subtotalSinIgv): void
    {
        $customerDoc = !empty($store->ruc) ? $store->ruc : ($order->user->document_number ?? '');
        $customerName = !empty($store->razon_social) ? $store->razon_social : ($store->trade_name ?? 'Tienda');
        $docType = Invoice::TYPE_FACTURA;

        $baseGravada = $subtotalSinIgv;
        $totalConIgv = round($baseGravada * (1 + self::IGV_RATE), 2);
        $igvAmount = round($totalConIgv - $baseGravada, 2);

        $series = $this->resolveSeries($docType);

        $invoice = Invoice::create([
            'store_id' => $store->id,
            'order_id' => $order->id,
            'invoice_number' => Invoice::generateInvoiceNumber(),
            'series' => $series,
            'number' => $this->resolveNextNumber($store, $series),
            'type' => $docType,
            'customer_name' => $customerName,
            'customer_ruc' => $customerDoc,
            'total' => $totalConIgv,
            'subtotal_sin_igv' => $baseGravada,
            'igv_amount' => $igvAmount,
            'provider' => 'nubefact',
            'status' => 'active',
            'sunat_status' => Invoice::SUNAT_STATUS_DRAFT,
            'emission_date' => now(),
            'history' => [
                [
                    'status' => Invoice::SUNAT_STATUS_DRAFT,
                    'note' => 'Generado automáticamente tras pago exitoso',
                    'timestamp' => now()->format('Y-m-d H:i'),
                    'user' => 'Sistema',
                ],
            ],
        ]);

        $customItems = [];
        foreach ($storeItems as $item) {
            $lineTotal = (float) $item->line_total;
            $qty = max((int) ($item->quantity ?? 1), 1);
            $unitPrice = round($lineTotal / $qty, 2);
            $igv = round($lineTotal * self::IGV_RATE, 2);
            $itemTotal = round($lineTotal + $igv, 2);

            $customItems[] = [
                'unidad_de_medida' => 'NIU',
                'descripcion' => $item->product_name,
                'cantidad' => (string) $qty,
                'valor_unitario' => $unitPrice,
                'precio_unitario' => round($unitPrice * (1 + self::IGV_RATE), 2),
                'subtotal' => $lineTotal,
                'tipo_de_igv' => '1',
                'igv' => $igv,
                'total' => $itemTotal,
            ];
        }

        try {
            $nubefactResponse = $this->nubefact->emitInvoice($invoice, $customItems);

            $invoice->addHistoryEntry(
                Invoice::SUNAT_STATUS_SENT_WAIT_CDR,
                'Enviado a NubeFact. Pendiente de CDR de SUNAT',
                'Sistema',
            );

            $invoice->sunat_status = $nubefactResponse['sunat_status'] ?? Invoice::SUNAT_STATUS_SENT_WAIT_CDR;
            $invoice->provider_invoice_id = $nubefactResponse['id'] ?? null;
            $invoice->pdf_url = $nubefactResponse['pdf_url'] ?? $this->nubefact->getPdfUrl($invoice);
            $invoice->authorization_code = $nubefactResponse['authorization_code'] ?? null;
            $invoice->qr_data = $nubefactResponse['qr_data'] ?? null;
            $invoice->xml_url = $nubefactResponse['xml_url'] ?? $this->nubefact->getXmlUrl($invoice);
            $invoice->cdr_url = $nubefactResponse['cdr_url'] ?? $this->nubefact->getCdrUrl($invoice);
            $invoice->save();

            Log::info('OrderPaymentService: factura emitida exitosamente', [
                'invoice_id' => $invoice->id,
                'store_id' => $store->id,
                'order_id' => $order->id,
                'provider_invoice_id' => $invoice->provider_invoice_id,
            ]);
        } catch (NubefactException $e) {
            $invoice->addHistoryEntry(
                Invoice::SUNAT_STATUS_REJECTED,
                'Error al enviar a NubeFact: ' . $e->getMessage(),
                'Sistema',
            );
            $invoice->sunat_status = Invoice::SUNAT_STATUS_REJECTED;
            $invoice->save();

            Log::error('OrderPaymentService: error NubeFact al emitir factura', [
                'invoice_id' => $invoice->id,
                'store_id' => $store->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    private function scheduleCommissionForStore(Order $order, Store $store, float $subtotalSinIgv): void
    {
        $commissionRate = (float) ($store->commission_rate ?? 0);

        $commissionRatePercent = $commissionRate * 100;

        $this->paymentScheduler->schedulePayment(
            storeId: $store->id,
            amount: $subtotalSinIgv,
            orderId: $order->id,
            commissionRate: $commissionRatePercent,
        );

        Log::info('OrderPaymentService: comisión programada', [
            'store_id' => $store->id,
            'order_id' => $order->id,
            'subtotal_sin_igv' => $subtotalSinIgv,
            'commission_rate' => $commissionRate,
            'commission_amount' => $subtotalSinIgv * $commissionRate,
        ]);
    }

    private function resolveSeries(string $type): string
    {
        $series = config('services.nubefact.series', []);

        return $series[$type] ?? ($type === Invoice::TYPE_FACTURA ? 'FFF1' : 'BBB1');
    }

    private function resolveNextNumber(Store $store, string $series): string
    {
        $lastInvoice = Invoice::where('store_id', $store->id)
            ->where('series', $series)
            ->orderBy('created_at', 'desc')
            ->first();

        if (! $lastInvoice || ! $lastInvoice->number) {
            return '0001';
        }

        $nextNumber = ((int) $lastInvoice->number) + 1;

        return str_pad((string) $nextNumber, 4, '0', STR_PAD_LEFT);
    }
}
