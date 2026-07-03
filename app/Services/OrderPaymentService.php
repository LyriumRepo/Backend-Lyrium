<?php

declare(strict_types=1);

namespace App\Services;

use App\Contracts\InvoiceProviderInterface;
use App\Exceptions\NubefactException;
use App\Models\Invoice;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\SellerPayment;
use App\Models\Store;
use App\Notifications\CommissionGeneratedNotification;
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

            $storesInvolved = $order->items->pluck('store_id')->unique()->values();
            $totalShipping = (float) ($order->shipping_cost ?? 0);
            $totalProductsSubtotal = (float) $order->items->sum('line_total');

            // Pre-calcular envío proporcional por tienda (último store absorbe el resto del redondeo)
            $storeShippings = [];
            $allocatedShipping = 0.0;
            $storesArray = $storesInvolved->all();
            $lastStoreId = end($storesArray);
            foreach ($storesArray as $sid) {
                if ($totalShipping <= 0 || $totalProductsSubtotal <= 0) {
                    $storeShippings[$sid] = 0.0;
                    continue;
                }
                $storeSubtotal = (float) $order->items->where('store_id', $sid)->sum('line_total');
                if ($sid === $lastStoreId) {
                    $storeShippings[$sid] = round($totalShipping - $allocatedShipping, 2);
                } else {
                    $portion = round($totalShipping * ($storeSubtotal / $totalProductsSubtotal), 2);
                    $storeShippings[$sid] = $portion;
                    $allocatedShipping += $portion;
                }
            }

            foreach ($storesInvolved as $storeId) {
                $store = Store::find($storeId);
                if (! $store) {
                    Log::warning('OrderPaymentService: store not found', ['store_id' => $storeId, 'order_id' => $order->id]);
                    continue;
                }

                $storeItems = $order->items->where('store_id', $storeId);
                $subtotalConIgv = (float) $storeItems->sum('line_total');

                if ($subtotalConIgv <= 0) {
                    continue;
                }

                $storeShipping = $storeShippings[$storeId] ?? 0.0;

                try {
                    $this->emitInvoiceForStore($order, $store, $storeItems, $subtotalConIgv, $storeShipping);
                } catch (\Throwable $e) {
                    Log::error('OrderPaymentService: error al emitir factura para tienda', [
                        'store_id' => $storeId,
                        'order_id' => $order->id,
                        'error' => $e->getMessage(),
                    ]);
                }

                try {
                    $this->scheduleCommissionForStore($order, $store, $subtotalConIgv);
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

    private function emitInvoiceForStore(Order $order, Store $store, iterable $storeItems, float $subtotalConIgv, float $shippingCost = 0.0): void
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

        // line_total viene CON IGV incluido → extraer base neta (solo productos)
        $baseGravada = round($subtotalConIgv / (1 + self::IGV_RATE), 2);
        $igvAmount   = round($baseGravada * self::IGV_RATE, 2);
        $totalConIgv = round($baseGravada + $igvAmount + $shippingCost, 2);

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
            'total' => $totalConIgv,            // incluye envío proporcional
            'subtotal_sin_igv' => $baseGravada, // solo productos (base para comisión)
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
            $lineTotal = (float) $item->line_total; // CON IGV
            $qty = max((int) ($item->quantity ?? 1), 1);
            $lineNetTotal = round($lineTotal / (1 + self::IGV_RATE), 2);
            $unitPrice = round($lineNetTotal / $qty, 2);
            $igv = round($lineNetTotal * self::IGV_RATE, 2);
            $itemTotal = $lineTotal; // precio con IGV = base + igv

            $customItems[] = [
                'unidad_de_medida' => 'NIU',
                'descripcion' => $item->product_name,
                'cantidad' => (string) $qty,
                'valor_unitario' => $unitPrice,
                'precio_unitario' => round($unitPrice * (1 + self::IGV_RATE), 2),
                'subtotal' => $lineNetTotal,
                'tipo_de_igv' => '1',
                'igv' => $igv,
                'total' => $itemTotal,
            ];
        }

        if ($shippingCost > 0) {
            $shippingBase = round($shippingCost / (1 + self::IGV_RATE), 2);
            $shippingIgv  = round($shippingBase * self::IGV_RATE, 2);
            $customItems[] = [
                'unidad_de_medida' => 'ZZ',
                'descripcion'      => 'Servicio de envío',
                'cantidad'         => '1',
                'valor_unitario'   => $shippingBase,
                'precio_unitario'  => $shippingCost,
                'subtotal'         => $shippingBase,
                'tipo_de_igv'      => '1',
                'igv'              => $shippingIgv,
                'total'            => $shippingCost,
            ];
        }

        $maxAttempts = 10;
        $lastException = null;

        for ($attempt = 1; $attempt <= $maxAttempts; $attempt++) {
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
                    'attempt' => $attempt,
                    'provider_invoice_id' => $invoice->provider_invoice_id,
                ]);

                $lastException = null;
                break;
            } catch (NubefactException $e) {
                // Número duplicado en Nubefact: incrementar y reintentar
                if ($e->getNubefactCode() === NubefactException::DUPLICATE_DOCUMENT && $attempt < $maxAttempts) {
                    $nextNum = (int) $invoice->number + 1;
                    $invoice->number = str_pad((string) $nextNum, 4, '0', STR_PAD_LEFT);
                    $invoice->save();

                    Log::warning('OrderPaymentService: número duplicado en NubeFact, reintentando', [
                        'invoice_id' => $invoice->id,
                        'store_id'   => $store->id,
                        'nuevo_numero' => $invoice->number,
                        'attempt' => $attempt,
                    ]);
                    continue;
                }

                $lastException = $e;
                break;
            }
        }

        if ($lastException !== null) {
            // Errores de configuración/conectividad: la factura queda en DRAFT
            // para poder reintentarse una vez corregido el .env.
            // Errores de validación o rechazo SUNAT: se marca REJECTED.
            $isInfraError = in_array($lastException->getNubefactCode(), [
                NubefactException::CONFIG_ERROR,
                NubefactException::CONNECTION_ERROR,
            ]);

            $failStatus = $isInfraError
                ? Invoice::SUNAT_STATUS_DRAFT
                : Invoice::SUNAT_STATUS_REJECTED;

            $invoice->addHistoryEntry(
                $failStatus,
                'Error al enviar a NubeFact: ' . $lastException->getMessage(),
                'Sistema',
            );
            $invoice->sunat_status = $failStatus;
            $invoice->save();

            Log::error('OrderPaymentService: error NubeFact al emitir factura', [
                'invoice_id' => $invoice->id,
                'store_id'   => $store->id,
                'nubefact_code' => $lastException->getNubefactCode(),
                'error'      => $lastException->getMessage(),
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

    private function scheduleCommissionForStore(Order $order, Store $store, float $subtotalConIgv): void
    {
        // Usar la tasa y monto escalonados ya calculados por CommissionService en los order_items
        $storeItems = $order->items->where('store_id', $store->id);

        $commissionAmount = (float) $storeItems->sum('commission_amount');
        // commission_rate viene como entero (ej: 15) desde CommissionTier
        $commissionRate   = (float) ($storeItems->first()?->commission_rate ?? 0);

        $baseGravada = round($subtotalConIgv / (1 + self::IGV_RATE), 2);

        $this->paymentScheduler->schedulePayment(
            storeId: $store->id,
            amount: $baseGravada,
            orderId: $order->id,
            commissionRate: $commissionRate,
        );

        $netAmount = round($subtotalConIgv - $commissionAmount, 2);
        $owner = $store->owner;
        if ($owner) {
            try {
                $owner->notify(new CommissionGeneratedNotification(
                    order: $order,
                    store: $store,
                    grossAmount: $subtotalConIgv,
                    commissionRate: $commissionRate,
                    commissionAmount: $commissionAmount,
                    netAmount: $netAmount,
                ));
            } catch (\Throwable $e) {
                Log::error('[OrderPayment] Error notificando CommissionGenerated', [
                    'order_id' => $order->id, 'store_id' => $store->id, 'error' => $e->getMessage(),
                ]);
            }
        }

        Log::info('OrderPaymentService: comisión programada (tasa escalonada)', [
            'store_id'         => $store->id,
            'order_id'         => $order->id,
            'subtotal_con_igv' => $subtotalConIgv,
            'commission_rate'  => $commissionRate,
            'commission_amount'=> $commissionAmount,
        ]);
    }

    private function resolveSeries(string $type): string
    {
        $series = config('services.nubefact.series', []);

        return $series[$type] ?? ($type === Invoice::TYPE_FACTURA ? 'FFF1' : 'BBB1');
    }

    private function resolveNextNumber(Store $store, string $series): string
    {
        // El correlativo de NubeFact es global por serie, no por tienda
        // (ver Invoice::resolveNextNumber). $store se conserva en la firma
        // para no tocar el call site, aunque ya no participa en el cálculo.
        return Invoice::resolveNextNumber($series);
    }
}
