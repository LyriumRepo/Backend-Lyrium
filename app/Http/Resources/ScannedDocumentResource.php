<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\DTOs\ScannedDocumentData;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

final class ScannedDocumentResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        /** @var ScannedDocumentData $data */
        $data = $this->resource;

        return match ($data->documentType) {
            'RECIBO_POR_HONORARIOS' => $this->toHonorarios($data),
            'FACTURA' => $this->toFactura($data),
            'BOLETA' => $this->toBoleta($data),
            'ESTADO_CUENTA_BCP' => $this->toEstadoCuenta($data),
            default => $this->toDefault($data),
        };
    }

    private function base(ScannedDocumentData $data): array
    {
        return [
            'success' => $data->rawText !== '',
            'document_type' => $data->documentType,
            'document_number' => $data->documentNumber,
            'issue_date' => $data->issueDate,
            'currency' => $data->currency,
            'issuer' => $data->issuer !== null ? [
                'name' => $data->issuer->name,
                'ruc' => $data->issuer->ruc,
                'address' => $data->issuer->address,
            ] : null,
            'customer' => $data->customer !== null ? [
                'name' => $data->customer->name,
                'ruc' => $data->customer->ruc,
                'address' => $data->customer->address,
            ] : null,
            'metadata' => [
                'is_scanned_image' => $data->isScannedImage,
                'source' => $data->source,
            ],
        ];
    }

    private function toHonorarios(ScannedDocumentData $data): array
    {
        return array_filter([
            ...$this->base($data),
            'payment' => $data->payment !== null ? [
                'payment_method' => $data->payment->paymentMethod,
                'amount_words' => $data->payment->amountWords,
                'gross_amount' => $data->payment->grossAmount,
                'retention_ir' => $data->payment->retentionIr,
                'net_amount' => $data->payment->netAmount,
                'currency' => $data->payment->currency,
            ] : null,
            'service' => $data->serviceDescription !== null ? [
                'description' => $data->serviceDescription,
            ] : null,
        ], fn ($v) => $v !== null);
    }

    private function toFactura(ScannedDocumentData $data): array
    {
        $result = [
            ...$this->base($data),
            'due_date' => $data->dueDate,
            'items' => array_map(fn ($item) => [
                'description' => $item->description,
                'quantity' => $item->quantity,
                'unit_price' => $item->unitPrice,
                'total' => $item->total,
            ], $data->items),
            'totals' => $data->totals !== null ? [
                'taxable_amount' => $data->totals->taxableAmount,
                'inafect_amount' => $data->totals->inafectAmount,
                'exempt_amount' => $data->totals->exemptAmount,
                'free_amount' => $data->totals->freeAmount,
                'igv' => $data->totals->igv,
                'isc' => $data->totals->isc,
                'icbper' => $data->totals->icbper,
                'other_taxes' => $data->totals->otherTaxes,
                'other_charges' => $data->totals->otherCharges,
                'discounts' => $data->totals->discounts,
                'grand_total' => $data->totals->grandTotal,
            ] : null,
            'amount_in_words' => $data->amountInWords,
        ];

        if ($data->authorizationDate !== null) {
            $result['metadata']['authorization_date'] = $data->authorizationDate;
        }

        return $result;
    }

    private function toBoleta(ScannedDocumentData $data): array
    {
        $result = [
            ...$this->base($data),
            'items' => array_map(fn ($item) => [
                'description' => $item->description,
                'quantity' => $item->quantity,
                'unit_price' => $item->unitPrice,
                'total' => $item->total,
            ], $data->items),
            'totals' => $data->totals !== null ? [
                'igv' => $data->totals->igv,
                'grand_total' => $data->totals->grandTotal,
            ] : null,
        ];

        return $result;
    }

    private function toEstadoCuenta(ScannedDocumentData $data): array
    {
        return [
            ...$this->base($data),
            'period' => $data->issueDate,
            'period_full' => $data->period,
            'opening_balance' => $data->openingBalance,
            'closing_balance' => $data->closingBalance,
            'lines' => array_map(fn ($line) => array_filter([
                'date' => $line->date,
                'description' => $line->description,
                'reference' => $line->reference,
                'charge' => $line->charge,
                'deposit' => $line->deposit,
                'glossary_key' => $line->glossaryKey,
                'glossary_description' => $line->glossaryDescription,
                'hour' => $line->hour,
                'med' => $line->med,
                'tipo' => $line->tipo,
                'place' => $line->place,
                'origen' => $line->origen,
                'num_op' => $line->numOp,
                'suc_age' => $line->sucAge,
                'balance' => $line->balance,
            ], fn ($v) => $v !== null), $data->bankStatementLines),
        ];
    }

    private function toDefault(ScannedDocumentData $data): array
    {
        return $this->base($data);
    }
}
