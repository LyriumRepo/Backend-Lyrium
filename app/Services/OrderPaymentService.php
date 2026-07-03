<?php

declare(strict_types=1);

namespace App\Services;

use App\Contracts\InvoiceProviderInterface;
use App\Exceptions\NubefactException;
use App\Models\Invoice;
use App\Models\Order;
use App\Models\SellerPayment;
use App\Models\Store;
use App\Services\AuditService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

final class OrderPaymentService
{
    private const IGV_RATE = 0.18;

    public function __construct(
        private readonly NubefactService $nubefact,
        private readonly InvoiceProviderInterface $provider,
        private readonly PaymentSchedulerService $paymentScheduler,
        private readonly AuditService $auditService,
    ) {}

    public static function fromConfig(): self
    {
        return new self(
            nubefact: NubefactService::fromConfig(),
            provider: \App\Services\NubefactProvider::fromConfig(),
            paymentScheduler: new PaymentSchedulerService(),
            auditService: app(AuditService::class),
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

        $this->auditService->record(
            event: 'payments.transaction.completed',
            module: 'payments',
            description: "Pago procesado para pedido #{$order->order_number} via {$paymentMethod}",
            auditable: $order,
            newValues: ['payment_status' => Order::PAYMENT_STATUS_PAID],
            success: true,
            source: AuditService::SOURCE_WEB,
            correlationId: (string) $order->id,
            metadata: ['payment_method' => $paymentMethod],
        );
    }

    private function emitInvoiceForStore(Order $order, Store $store, iterable $storeItems, float $subtotalSinIgv): void
    {
        // Nombre visible: nombre_comercial > store_name (accessor) > razon_social > trade_name
        $customerName = $store->nombre_comercial
            ?: $store->store_name
            ?: $store->razon_social
            ?: $store->trade_name
            ?: 'Tienda';

        // RUC de la tienda; si no tiene, usar el RUC registrado en Nubefact (Lyrium)
        $customerDoc = $store->ruc ?: config('services.nubefact.ruc', '20600695771');
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

        $errors = $this->validateInvoiceData($customerDoc, $customerName, $baseGravada, $igvAmount);
        if (!empty($errors)) {
            $errorMsg = 'Factura no enviada: ' . implode('; ', $errors);
            $invoice->addHistoryEntry(Invoice::SUNAT_STATUS_DRAFT, $errorMsg, 'Sistema');
            $invoice->save();
            Log::warning('OrderPaymentService: pre-validación fallida', [
                'invoice_id' => $invoice->id,
                'store_id' => $store->id,
                'order_id' => $order->id,
                'errors' => $errors,
            ]);
            return;
        }

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
            // Errores de configuración/conectividad: la factura queda en DRAFT
            // para poder reintentarse una vez corregido el .env.
            // Errores de validación o rechazo SUNAT: se marca REJECTED.
            $isInfraError = in_array($e->getNubefactCode(), [
                NubefactException::CONFIG_ERROR,
                NubefactException::CONNECTION_ERROR,
            ]);

            $failStatus = $isInfraError
                ? Invoice::SUNAT_STATUS_DRAFT
                : Invoice::SUNAT_STATUS_REJECTED;

            $invoice->addHistoryEntry(
                $failStatus,
                'Error al enviar a NubeFact: ' . $e->getMessage(),
                'Sistema',
            );
            $invoice->sunat_status = $failStatus;
            $invoice->save();

            Log::error('OrderPaymentService: error NubeFact al emitir factura', [
                'invoice_id' => $invoice->id,
                'store_id'   => $store->id,
                'nubefact_code' => $e->getNubefactCode(),
                'error'      => $e->getMessage(),
            ]);
        }
    }

    /**
     * @return string[] Lista de errores de validación (vacío si todo ok)
     */
    private function validateInvoiceData(string $ruc, string $customerName, float $baseGravada, float $igvAmount): array
    {
        $errors = [];

        if (empty(trim($customerName))) {
            $errors[] = 'Razón social del cliente vacía';
        }

        if ($baseGravada <= 0) {
            $errors[] = 'Subtotal base debe ser mayor a 0';
        }

        $expectedIgv = round($baseGravada * self::IGV_RATE, 2);
        if (abs($igvAmount - $expectedIgv) > 0.01) {
            $errors[] = "IGV calculado ({$igvAmount}) no coincide con el esperado ({$expectedIgv})";
        }

        $digits = preg_replace('/\D/', '', $ruc);
        if (strlen($digits) !== 11) {
            $errors[] = "RUC debe tener exactamente 11 dígitos numéricos (recibido: {$ruc})";
        } else {
            $sum = 0;
            $weights = [5, 4, 3, 2, 7, 6, 5, 4, 3, 2];
            for ($i = 0; $i < 10; $i++) {
                $sum += (int) $digits[$i] * $weights[$i];
            }
            $remainder = $sum % 11;
            $checkDigit = $remainder === 0 ? 0 : 11 - $remainder;
            if ($checkDigit === 10) {
                $checkDigit = 1;  // SUNAT: cuando 11 - resto == 10, el dígito verificador es 1
            }
            if ($checkDigit !== (int) $digits[10]) {
                $errors[] = "Dígito de verificación del RUC inválido ({$ruc})";
            }
        }

        return $errors;
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
