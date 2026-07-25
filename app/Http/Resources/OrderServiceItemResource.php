<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

final class OrderServiceItemResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => (string) $this->id,
            'serviceId' => $this->service_id,
            'serviceName' => $this->service_name,
            'storeId' => $this->store_id,
            'storeName' => $this->store_name_snapshot ?? $this->whenLoaded('store', fn () => $this->store->store_name ?? $this->store->trade_name, null),
            'specialistName' => $this->specialist_name_snapshot ?? null,
            'quantity' => (int) $this->quantity,
            'unitPrice' => (float) $this->unit_price,
            'lineTotal' => (float) $this->line_total,
            'status' => trim($this->status),
            'appointmentDate' => $this->appointment_date?->toIso8601String(),
            'startTime' => $this->appointment_date?->format('H:i'),
            'endTime' => $this->appointment_date
                ? $this->appointment_date->copy()->addMinutes($this->duration_minutes ?? 60)->format('H:i')
                : null,
            'modality' => $this->modality,
            'durationMinutes' => $this->duration_minutes,
            'serviceBookingId' => $this->service_booking_id,
            'bookingStatus' => $this->whenLoaded('serviceBooking', fn () => $this->serviceBooking->status, null),
            'customerValidatedAt' => $this->whenLoaded('serviceBooking', fn () => $this->serviceBooking->customer_validated_at?->toIso8601String(), null),
            'createdAt' => $this->created_at?->toIso8601String(),
            'updatedAt' => $this->updated_at?->toIso8601String(),
        ];
    }
}
