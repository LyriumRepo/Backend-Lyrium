<?php

declare(strict_types=1);

namespace App\Services;

use App\Contracts\InvoiceProviderInterface;
use App\Exceptions\NubefactException;
use App\Models\Invoice;
use Illuminate\Support\Facades\Log;

final class NubefactService
{
    private const IGV_RATE = 0.18;

    public function __construct(
        private readonly InvoiceProviderInterface $provider,
    ) {}

    public static function fromConfig(): self
    {
        return new self(NubefactProvider::fromConfig());
    }

    public function emitInvoice(Invoice $invoice, ?array $customItems = null): array
    {
        $totalConIgv = (float) $invoice->total;
        $baseGravada = $totalConIgv / (1 + self::IGV_RATE);
        $totalIgv = $totalConIgv - $baseGravada;

        if ($customItems !== null) {
            $items = $customItems;
        } else {
            $order = $invoice->order()->with('items')->first();
            $shippingCost = $order ? (float) ($order->shipping_cost ?? 0) : 0.0;
            $items = $this->buildItems($order?->items, $baseGravada, $invoice->customer_name, $shippingCost);
        }

        $payload = [
            'operacion' => 'generar_comprobante',
            'tipo_de_comprobante' => $this->mapTypeToNubefact($invoice->type),
            'serie' => $invoice->series,
            'numero' => $invoice->number,
            'sunat_transaction' => '1',
            'cliente_tipo_de_documento' => $this->getCustomerDocType($invoice->customer_ruc),
            'cliente_numero_de_documento' => $invoice->customer_ruc,
            'cliente_denominacion' => $invoice->customer_name,
            'cliente_email' => '',
            'fecha_de_emision' => now('America/Lima')->format('d-m-Y'),
            'moneda' => '1',
            'porcentaje_de_igv' => '18.00',
            'total_gravada' => round($baseGravada, 2),
            'total_igv' => round($totalIgv, 2),
            'total' => round($totalConIgv, 2),
            'enviar_automaticamente_a_la_sunat' => 'true',
            'enviar_automaticamente_al_cliente' => 'false',
            'items' => $items,
        ];

        $this->logDebug('emitInvoice — Enviando a NubeFact', [
            'payload' => $payload,
        ]);

        $response = $this->provider->emitInvoice($payload);

        $this->logDebug('emitInvoice — Respuesta de NubeFact', [
            'response' => $response,
        ]);

        return $this->parseProviderResponse($response);
    }

    private function buildItems(?iterable $orderItems, float $baseGravada, string $fallbackDesc, float $shippingCost = 0.0): array
    {
        $items = [];

        $totalBase = 0.0;
        $segments = [];

        if ($orderItems !== null) {
            foreach ($orderItems as $oi) {
                $lt = (float) ($oi->line_total ?? 0);
                if ($lt > 0) {
                    $totalBase += $lt;
                    $segments[] = ['type' => 'product', 'item' => $oi, 'base' => $lt];
                }
            }
        }

        if ($shippingCost > 0) {
            $totalBase += $shippingCost;
            $segments[] = ['type' => 'shipping', 'base' => $shippingCost];
        }

        // Sin conceptos: un item genérico
        if ($totalBase <= 0) {
            $igv = round($baseGravada * self::IGV_RATE, 2);
            $itemTotal = round($baseGravada + $igv, 2);
            return [[
                'unidad_de_medida' => 'NIU',
                'descripcion' => $fallbackDesc,
                'cantidad' => '1',
                'valor_unitario' => round($baseGravada, 2),
                'precio_unitario' => $itemTotal,
                'subtotal' => round($baseGravada, 2),
                'tipo_de_igv' => '1',
                'igv' => $igv,
                'total' => $itemTotal,
            ]];
        }

        $ratio = $baseGravada / $totalBase;
        $allocated = 0.0;
        $count = count($segments);

        foreach ($segments as $i => $segment) {
            $isLast = ($i === $count - 1);

            if ($segment['type'] === 'product') {
                $oi = $segment['item'];
                $rawBase = $segment['base'];
                $qty = max((int) ($oi->quantity ?? 1), 1);

                $itemBase = $isLast
                    ? round($baseGravada - $allocated, 2)
                    : round($rawBase * $ratio, 2);
                $allocated += $itemBase;

                $unitBase = $qty > 0 ? round($itemBase / $qty, 2) : 0;
                $igv = round($itemBase * self::IGV_RATE, 2);
                $itemTotal = round($itemBase + $igv, 2);

                $items[] = [
                    'unidad_de_medida' => 'NIU',
                    'descripcion' => $oi->product_name ?? $fallbackDesc,
                    'cantidad' => (string) $qty,
                    'valor_unitario' => $unitBase,
                    'precio_unitario' => $itemTotal,
                    'subtotal' => $itemBase,
                    'tipo_de_igv' => '1',
                    'igv' => $igv,
                    'total' => $itemTotal,
                ];
            } else {
                // Shipping
                $rawBase = $segment['base'];

                $itemBase = $isLast
                    ? round($baseGravada - $allocated, 2)
                    : round($rawBase * $ratio, 2);
                $allocated += $itemBase;

                $igv = round($itemBase * self::IGV_RATE, 2);
                $itemTotal = round($itemBase + $igv, 2);

                $items[] = [
                    'unidad_de_medida' => 'ZZ',
                    'descripcion' => 'Servicio de envío',
                    'cantidad' => '1',
                    'valor_unitario' => $itemBase,
                    'precio_unitario' => $itemTotal,
                    'subtotal' => $itemBase,
                    'tipo_de_igv' => '1',
                    'igv' => $igv,
                    'total' => $itemTotal,
                ];
            }
        }

        return $items;
    }

    public function retryInvoice(Invoice $invoice): array
    {
        return $this->emitInvoice($invoice);
    }

    public function getInvoiceStatus(Invoice $invoice): ?array
    {
        if (empty($invoice->provider_invoice_id)) {
            return null;
        }

        return $this->provider->getInvoiceStatus($invoice->provider_invoice_id);
    }

    public function getPdfUrl(Invoice $invoice): ?string
    {
        return $this->provider->getInvoicePdfUrl($invoice->provider_invoice_id);
    }

    public function getXmlUrl(Invoice $invoice): ?string
    {
        return $this->provider->getInvoiceXmlUrl($invoice->provider_invoice_id);
    }

    public function getCdrUrl(Invoice $invoice): ?string
    {
        return $this->provider->getCdrUrl($invoice->provider_invoice_id);
    }

    private function parseProviderResponse(array $response): array
    {
        return [
            'id' => $response['key'] ?? $response['id'] ?? null,
            'pdf_url' => $response['enlace_del_pdf'] ?? $response['enlace'] ?? null,
            'xml_url' => $response['enlace_del_xml'] ?? $response['xml_url'] ?? null,
            'cdr_url' => $response['enlace_del_cdr'] ?? $response['cdr_url'] ?? null,
            'authorization_code' => $response['codigo_hash'] ?? $response['authorization_code'] ?? null,
            'qr_data' => $response['cadena_para_codigo_qr'] ?? $response['qr_data'] ?? null,
            'sunat_status' => $this->resolveSunatStatus($response),
            'sunat_response' => $response['sunat_description'] ?? $response['sunat_note'] ?? null,
            'raw' => $response,
        ];
    }

    private function resolveSunatStatus(array $response): string
    {
        $aceptada = $response['aceptada_por_sunat'] ?? null;

        if ($aceptada === 'true' || $aceptada === true) {
            return Invoice::SUNAT_STATUS_ACCEPTED;
        }

        $estado = $response['estado'] ?? $response['sunat_status'] ?? $response['sunat_responsecode'] ?? '';

        return match (strtolower((string) $estado)) {
            'aceptado', 'aceptada', 'accepted', '0' => Invoice::SUNAT_STATUS_ACCEPTED,
            'observado', 'observada', 'observed' => Invoice::SUNAT_STATUS_OBSERVED,
            'rechazado', 'rechazada', 'rejected' => Invoice::SUNAT_STATUS_REJECTED,
            'pendiente', 'pending', 'procesando' => Invoice::SUNAT_STATUS_SENT_WAIT_CDR,
            default => Invoice::SUNAT_STATUS_SENT_WAIT_CDR,
        };
    }

    private function mapTypeToNubefact(string $type): string
    {
        return match ($type) {
            Invoice::TYPE_FACTURA => '1',
            Invoice::TYPE_BOLETA => '2',
            Invoice::TYPE_NOTA_CREDITO => '3',
            default => '1',
        };
    }

    private function getCustomerDocType(string $ruc): string
    {
        if (strlen($ruc) === 11) {
            return '6';
        }

        return '1';
    }

    private function logDebug(string $message, array $context = []): void
    {
        Log::debug("NubefactService::{$message}", $context);
    }

    private function logError(string $message, array $context = []): void
    {
        Log::error("NubefactService::{$message}", $context);
    }
}
