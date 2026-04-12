<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

final class ServiceBookingResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'appointment_date' => $this->appointment_date?->toIso8601String(),
            'status' => $this->status,
            'total_price' => (float) $this->total_price,
            'payment_method' => $this->payment_method,
            'payment_status' => $this->payment_status,
            'customer_notes' => $this->when(
                $request->user()?->id === $this->user_id || $request->user()?->hasRole('administrator'),
                $this->customer_notes
            ),
            'seller_notes' => $this->when(
                $request->user()?->hasRole('seller', 'administrator'),
                $this->seller_notes
            ),
            'confirmed_at' => $this->confirmed_at?->toIso8601String(),
            'cancelled_at' => $this->cancelled_at?->toIso8601String(),
            'service' => $this->whenLoaded('service', fn () => [
                'id' => $this->service->id,
                'name' => $this->service->name,
                'duration_minutes' => $this->service->duration_minutes,
            ]),
            'user' => $this->whenLoaded('user', fn () => [
                'id' => $this->user->id,
                'name' => $this->user->name,
                'email' => $this->user->email,
            ]),
            'schedule' => $this->whenLoaded('schedule', fn () => new ServiceScheduleResource($this->schedule)),
            'can_cancel' => $this->when($request->user(), fn () => $this->canCancel()),
            'can_reschedule' => $this->when($request->user(), fn () => $this->canReschedule()),
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
