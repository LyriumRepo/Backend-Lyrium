<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * ServiceBookingResource
 *
 * Estructura de salida alineada con el tipo `ServiceBooking` de serviceRepository.ts:
 *
 * interface ServiceBooking {
 *   id, service_id, service_name, store_id, store_name,
 *   customer_id, customer_name, customer_email, customer_phone,
 *   date, start_time, end_time,
 *   status, payment_method, payment_status, payment_amount,
 *   notes, seller_notes, reschedule_token, no_show_reason,
 *   created_at, updated_at
 * }
 *
 * CAMBIOS respecto al Resource anterior:
 *  - Agrega: service_id, service_name, store_id, store_name,
 *             customer_id, customer_name, customer_email, customer_phone,
 *             date (YYYY-MM-DD), start_time (HH:MM), end_time (HH:MM),
 *             payment_amount, notes (alias de customer_notes),
 *             no_show_reason
 *  - Renombra: total_price → payment_amount, customer_notes → notes
 *  - Elimina: appointment_date (reemplazado por date+start_time+end_time),
 *             can_cancel, can_reschedule, confirmed_at, cancelled_at, schedule
 *  - Mantiene: reschedule_token, seller_notes
 *
 * Visibilidad:
 *  - `seller_notes` solo para seller/admin (igual que antes)
 *  - `reschedule_token` solo para el cliente dueño de la reserva
 *  - `notes` (customer_notes) para el cliente o admin
 */
final class ServiceBookingResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $currentUser = $request->user();
        $isOwner = $currentUser?->id === $this->user_id;
        $isAdmin = $currentUser?->hasRole('administrator');
        $isSeller = $currentUser?->hasRole('seller') || $isAdmin;

        // Calcular end_time: appointment_date + duration del servicio
        $appointmentDate = $this->appointment_date; // Carbon instance (cast en el modelo)
        $durationMinutes = $this->whenLoaded('service', fn () => $this->service->duration_minutes, 60);
        $endTime = $appointmentDate?->copy()->addMinutes($durationMinutes);

        return [
            // ── Identificación ──────────────────────────────────────────────
            'id' => $this->id,

            'specialist' => $this->whenLoaded('specialist', function () {
                return [
                    'id' => $this->specialist->id,
                    'name' => trim($this->specialist->nombres.''.$this->specialist->apellidos),
                ];
            }),

            // ── Servicio (datos aplanados + relación) ───────────────────────
            'service_id' => $this->service_id,
            'service_name' => $this->whenLoaded('service', fn () => $this->service->name ?? '', ''),
            'store_id' => $this->whenLoaded(
                'service',
                fn () => $this->service->store_id ?? null,
                null
            ),
            'store_name' => $this->whenLoaded(
                'service',
                fn () => $this->service->store?->store_name
                    ?? $this->service->store?->trade_name
                    ?? '',
                ''
            ),

            // ── Cliente ─────────────────────────────────────────────────────
            'customer_id' => $this->user_id,
            'customer_name' => $this->whenLoaded('user', fn () => $this->user->name ?? '', ''),
            'customer_email' => $this->whenLoaded('user', fn () => $this->user->email ?? '', ''),
            'customer_phone' => $this->whenLoaded('user', fn () => $this->user->phone ?? '', ''),

            // ── Fecha y hora ─────────────────────────────────────────────────
            // El frontend espera: date = 'YYYY-MM-DD', start_time = 'HH:MM', end_time = 'HH:MM'
            'date' => $appointmentDate?->toDateString(),      // '2026-06-15'
            'start_time' => $appointmentDate?->format('H:i'),        // '10:00'
            'end_time' => $endTime?->format('H:i'),                // '11:00'

            // ── Estado ───────────────────────────────────────────────────────
            'status' => $this->status, // 'pending'|'confirmed'|'completed'|'cancelled'|'no_show'|'on_the_way'

            // ── Calificación (si el cliente ya calificó esta reserva) ─────────
            'review' => $this->whenLoaded('review', fn () => $this->review ? [
                'rating' => $this->review->rating,
                'comment' => $this->review->comment,
            ] : null),

            // ── Tipo de atención ─────────────────────────────────────────────
            'is_home_service' => (bool) ($this->service?->is_home_service ?? false),

            // ── Pago ─────────────────────────────────────────────────────────
            'payment_method' => $this->payment_method,
            'payment_status' => $this->payment_status,
            'payment_amount' => (float) $this->total_price, // total_price → payment_amount

            // ── Notas ────────────────────────────────────────────────────────
            // notes (customer_notes): visible para el dueño y admin
            'notes' => $this->when(
                $isOwner || $isAdmin,
                $this->customer_notes
            ),

            // seller_notes: solo para seller/admin
            'seller_notes' => $this->when(
                $isSeller,
                $this->seller_notes
            ),

            // ── Token de reagendamiento (solo para el cliente dueño) ─────────
            'reschedule_token' => $this->when(
                $isOwner,
                $this->reschedule_token
            ),

            // ── No show ──────────────────────────────────────────────────────
            // seller_notes se usa como no_show_reason en el modelo actual
            // Si agregas un campo dedicado en DB, reemplazar aquí
            'no_show_reason' => $this->when(
                $this->status === 'no_show' && $isSeller,
                $this->seller_notes
            ),

            // ── Timestamps ───────────────────────────────────────────────────
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),

            // ── Campos opcionales para el panel del vendedor ─────────────────
            // No están en el tipo TS base pero son útiles en la UI
            'confirmed_at' => $this->when($isSeller, $this->confirmed_at?->toIso8601String()),
            'cancelled_at' => $this->when($isSeller, $this->cancelled_at?->toIso8601String()),
            'can_cancel' => $this->when($currentUser !== null, fn () => $this->canCancel()),
            'can_reschedule' => $this->when($currentUser !== null, fn () => $this->canReschedule()),
        ];
    }
}
