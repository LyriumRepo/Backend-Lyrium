<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * ServiceResource
 *
 * Estructura de salida alineada con el tipo `Service` de serviceRepository.ts,
 * unificada con los campos de configuración avanzada y especialistas de MedConnect.
 */
final class ServiceResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        // Extraer configuraciones dinámicas del campo JSON settings
        $settings    = is_array($this->settings) ? $this->settings : [];
        $meetingLink = $settings['meeting_link'] ?? null;
        $isVirtual   = ! empty($meetingLink) || ($settings['is_virtual'] ?? false);

        return [
            // ── Identificación ──────────────────────────────────────────────
            'id'         => $this->id,
            'store_id'   => $this->store_id,
            'store_name' => $this->whenLoaded(
                'store',
                fn() => $this->store->store_name ?? $this->store->trade_name ?? '',
                ''
            ),

            // ── Contenido ───────────────────────────────────────────────────
            'name'        => $this->name,
            // slug se mapea dinámicamente si no existe el campo en la BD
            'slug'        => $this->slug ?? \Illuminate\Support\Str::slug($this->name) . '-' . $this->id,
            'description' => $this->description ?? '',

            // ── Precio, duración y configuración del descanso ───────────────
            'duration_minutes'      => (int) $this->duration_minutes,
            'buffer_minutes'        => (int) ($this->buffer_minutes ?? 10), // Nuevo campo del modal
            'price'                 => (float) $this->price,
            'currency'              => 'PEN',

            // ── Categoría (string plano) ────────────────────────────────────
            'category' => $this->whenLoaded(
                'category',
                fn() => $this->category?->name ?? '',
                ''
            ),

            // ── Imagen (Spatie Media Library o campo directo de fallback) ───
            'image' => $this->when(
                true,
                function () {
                    if (method_exists($this->resource, 'getFirstMediaUrl')) {
                        $url = $this->getFirstMediaUrl('images');
                        return $url ?: ($this->image ?? null);
                    }
                    return $this->image ?? null;
                }
            ),

            // ── Estado y políticas de la cita ───────────────────────────────
            'status'              => $this->status,             // 'active' | 'draft' | 'inactive'
            'cancellation_policy' => $this->cancellation_policy, // 'flexible' | 'strict' | 'no_refund'
            'cancellation_hours'  => (int) ($settings['cancellation_hours'] ?? 24),

            // ── Flags de comportamiento y restricciones ─────────────────────
            'requires_payment'      => (bool) ($settings['requires_payment'] ?? true),
            'is_virtual'            => $isVirtual,
            'meeting_link'          => $meetingLink,
            'max_bookings_per_slot' => (int) ($settings['max_bookings_per_slot'] ?? 1),
            'is_home_service'       => (bool) ($this->is_home_service ?? false), // Nuevo toggle a domicilio
            'booking_advance_hours' => (int) ($this->booking_advance_hours ?? 24), // Nueva anticipación
            'max_capacity'          => (int) ($this->max_capacity ?? 1), // Nuevo cupo por sesión

            // ── Horarios (Mapeo plano con indexación numérica de días) ──────
            'schedule' => $this->whenLoaded(
                'schedules',
                fn() => $this->schedules->map(fn($s) => [
                    'id'               => $s->id,
                    'day_of_week'      => is_numeric($s->day_of_week) ? (int) $s->day_of_week : (int) $this->dayOfWeekToInt((string) $s->day_of_week),
                    'start_time'       => $s->start_time ? substr((string) $s->start_time, 0, 5) : null,
                    'end_time'         => $s->end_time   ? substr((string) $s->end_time, 0, 5)   : null,
                    'is_available'     => (bool) $s->is_active,
                    'max_appointments' => (int) $s->max_appointments,
                ])->values()->all(),
                []
            ),

            // ── Especialistas Asignados (Relación de Muchos a Muchos) ────────
            'specialists' => $this->whenLoaded(
                'specialists',
                fn() => $this->specialists->map(fn($sp) => [
                    'id'              => $sp->id,
                    'nombres'         => $sp->nombres,
                    'apellidos'       => $sp->apellidos,
                    'document_type'   => $sp->document_type,
                    'document_number' => $sp->document_number,
                    'especialidad'    => $sp->especialidad,
                    'availability'    => $sp->availability,
                    'foto'            => $sp->foto,
                ])->values()->all(),
                []
            ),

            // ── Timestamps ──────────────────────────────────────────────────
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }

    /**
     * Convierte el enum string de day_of_week al número que espera el frontend.
     * El tipo ServiceSchedule en TS usa number (0-6).
     */
    private function dayOfWeekToInt(string $day): int
    {
        return match (strtolower($day)) {
            'monday'    => 0,
            'tuesday'   => 1,
            'wednesday' => 2,
            'thursday'  => 3,
            'friday'    => 4,
            'saturday'  => 5,
            'sunday'    => 6,
            default     => 0,
        };
    }
}
