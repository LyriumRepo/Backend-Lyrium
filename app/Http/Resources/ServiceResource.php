<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * ServiceResource — FIX: specialists[] ahora incluye sus schedules propios.
 *
 * PROBLEMA ANTERIOR:
 *   specialists[] se cargaba desde el pivot service_specialist pero sin
 *   los schedules del especialista para ESTE servicio. La relación
 *   $specialist->schedules() devuelve TODOS sus horarios de todos los
 *   servicios, no solo los de este servicio.
 *
 * SOLUCIÓN:
 *   Filtrar $this->schedules (ya cargada) por specialist_id en lugar de
 *   hacer una query adicional. Cero N+1.
 *
 * REQUISITO: El controller debe cargar: with(['store','schedules','category','specialists'])
 *   Los schedules YA se cargan en findOrFail() y paginatePublic(). ✓
 */
final class ServiceResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $settings = is_array($this->settings) ? $this->settings : [];
        $meetingLink = $settings['meeting_link'] ?? null;
        $isVirtual = ! empty($meetingLink) || ($settings['is_virtual'] ?? false);

        return [
            // ── Identificación ──────────────────────────────────────────────
            'id' => $this->id,
            'store_id' => $this->store_id,
            'store_name' => $this->whenLoaded(
                'store',
                fn () => $this->store->store_name ?? $this->store->trade_name ?? '',
                ''
            ),
            'store_logo' => $this->whenLoaded(
                'store',
                fn () => $this->store->getMediaUrl('logo'),
                null
            ),
            'store_logo_marketplace' => $this->whenLoaded(
                'store',
                fn () => $this->store->getMediaUrl('logo_marketplace'),
                null
            ),

            // ── Contenido ───────────────────────────────────────────────────
            'name' => $this->name,
            'slug' => $this->slug ?? \Illuminate\Support\Str::slug($this->name).'-'.$this->id,
            'description' => $this->description ?? '',
            'benefits' => $this->benefits ?? '',

            // ── Precio y configuración ───────────────────────────────────────
            'duration_minutes' => (int) $this->duration_minutes,
            'buffer_minutes' => (int) ($this->buffer_minutes ?? 10),
            'price' => (float) $this->price,
            'currency' => 'PEN',

            // ── Categoría ────────────────────────────────────────────────────
            'category' => $this->whenLoaded(
                'category',
                fn () => $this->category
                    ? ($this->category->parent
                        ? $this->category->parent->name.' > '.$this->category->name
                        : $this->category->name)
                    : '',
                ''
            ),
            'category_id' => $this->category_id,
            'parent_category_id' => $this->whenLoaded(
                'category',
                fn () => $this->category?->parent_id ?? null,
                null
            ),

            // ── Imagen ───────────────────────────────────────────────────────
            'image' => $this->when(true, function () {
                if (method_exists($this->resource, 'getFirstMediaUrl')) {
                    $url = $this->getFirstMediaUrl('images');

                    return $url ?: ($this->image ?? null);
                }

                return $this->image ?? null;
            }),

            // ── Sticker / Etiquetas ──────────────────────────────────────────
            'sticker' => $this->sticker,
            'discount_percentage' => $this->discount_percentage
                ? (float) $this->discount_percentage
                : null,
            'settings' => $settings,

            // ── Estado ───────────────────────────────────────────────────────
            'status' => $this->status === \App\Models\Service::STATUS_ACTIVE ? 'approved' : $this->status,
            'cancellation_policy' => $this->cancellation_policy,
            'cancellation_hours' => (int) ($settings['cancellation_hours'] ?? 24),

            // ── Flags ─────────────────────────────────────────────────────────
            'requires_payment' => (bool) ($settings['requires_payment'] ?? true),
            'is_virtual' => $isVirtual,
            'meeting_link' => $meetingLink,
            'max_bookings_per_slot' => (int) ($settings['max_bookings_per_slot'] ?? 1),
            'is_home_service' => (bool) ($this->is_home_service ?? false),
            'booking_advance_hours' => (int) ($this->booking_advance_hours ?? 24),
            'max_capacity' => (int) ($this->max_capacity ?? 1),

            // ── Horarios planos (vista de servicio completo, sin specialist) ─
            // Útil para la vista general. day_of_week se devuelve como int (0-6).
            'schedule' => $this->whenLoaded(
                'schedules',
                fn () => $this->schedules->map(fn ($s) => [
                    'id' => $s->id,
                    'specialist_id' => $s->specialist_id, // ← AÑADIDO para debug
                    'day_of_week' => is_numeric($s->day_of_week)
                        ? (int) $s->day_of_week
                        : (int) $this->dayOfWeekToInt((string) $s->day_of_week),
                    'start_time' => $s->start_time ? substr((string) $s->start_time, 0, 5) : null,
                    'end_time' => $s->end_time ? substr((string) $s->end_time, 0, 5) : null,
                    'is_available' => (bool) $s->is_active,
                    'max_appointments' => (int) $s->max_appointments,
                ])->values()->all(),
                []
            ),

            // ── Especialistas con SUS PROPIOS horarios para este servicio ────
            //
            // FIX CLAVE: en lugar de hacer $specialist->schedules() (que devuelve
            // todos sus horarios de todos los servicios), filtramos $this->schedules
            // (ya cargada) por specialist_id. Es O(n) en memoria, cero queries extra.
            //
            'specialists' => $this->whenLoaded(
                'specialists',
                function () {
                    // Agrupar schedules de ESTE servicio por specialist_id
                    // usando la colección ya cargada (evita N+1).
                    $schedulesBySpecialist = $this->whenLoaded(
                        'schedules',
                        fn () => $this->schedules->groupBy('specialist_id'),
                        collect()
                    );

                    return $this->specialists->map(function ($sp) use ($schedulesBySpecialist) {
                        // Horarios de este especialista para este servicio
                        $spSchedules = $schedulesBySpecialist[$sp->id] ?? collect();

                        return [
                            'id' => $sp->id,
                            'nombres' => $sp->nombres,
                            'apellidos' => $sp->apellidos,
                            'nombre_completo' => trim($sp->nombres.' '.$sp->apellidos),
                            'document_type' => $sp->document_type,
                            'document_number' => $sp->document_number,
                            'especialidad' => $sp->especialidad,
                            'sub_especialidad' => $sp->sub_especialidad,
                            'anios_experiencia' => $sp->anios_experiencia,
                            'availability' => $sp->availability,
                            'foto' => $sp->foto,
                            // Horarios agrupados por día (listos para el frontend)
                            'schedules' => $spSchedules->map(fn ($s) => [
                                'id' => $s->id,
                                'service_id' => $s->service_id,
                                'day_of_week' => strtolower((string) $s->day_of_week),
                                'start_time' => $s->start_time ? substr((string) $s->start_time, 0, 5) : null,
                                'end_time' => $s->end_time ? substr((string) $s->end_time, 0, 5) : null,
                                'orden_bloque' => (int) ($s->orden_bloque ?? 1),
                                'max_appointments' => (int) $s->max_appointments,
                                'is_active' => (bool) $s->is_active,
                            ])->values()->all(),
                        ];
                    })->values()->all();
                },
                []
            ),

            // ── Rating ───────────────────────────────────────────────────────
            // Promedio/conteo real de reseñas (vía Service::reviews(), que
            // atraviesa service_bookings -> reviews). Usa withAvg/withCount
            // si el controller los precarga (ver ServiceController), si no
            // cae al accessor con query en vivo (Service::getAverageRatingAttribute).
            'rating' => [
                'average' => (float) ($this->average_rating ?? 0),
                'count' => (int) ($this->review_count ?? 0),
            ],

            // ── Timestamps ──────────────────────────────────────────────────
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
            'reviewed_at' => $this->reviewed_at?->toIso8601String(),
        ];
    }

    private function dayOfWeekToInt(string $day): int
    {
        return match (strtolower($day)) {
            'monday' => 0,
            'tuesday' => 1,
            'wednesday' => 2,
            'thursday' => 3,
            'friday' => 4,
            'saturday' => 5,
            'sunday' => 6,
            default => 0,
        };
    }
}
