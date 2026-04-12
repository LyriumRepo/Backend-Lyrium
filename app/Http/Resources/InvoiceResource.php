<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

final class InvoiceResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => (string) $this->id,
            'orderId' => $this->order_id,
            'invoiceNumber' => $this->invoice_number,
            'nit' => $this->nit,
            'businessName' => $this->business_name,
            'provider' => $this->provider,
            'providerInvoiceId' => $this->provider_invoice_id,
            'qrData' => $this->qr_data,
            'pdfUrl' => $this->pdf_url,
            'authorizationCode' => $this->authorization_code,
            'total' => (float) $this->total,
            'status' => $this->status,
            'order' => $this->whenLoaded('order', fn () => [
                'id' => (string) $this->order->id,
                'orderNumber' => $this->order->order_number,
                'total' => (float) $this->order->total,
                'status' => $this->order->status,
            ]),
            'createdAt' => $this->created_at?->toIso8601String(),
            'updatedAt' => $this->updated_at?->toIso8601String(),
        ];
    }
}
