<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * SpecialistResource
 *
 * Regla de privacidad (del Documento de Campos):
 *   ✗ NO exponer al cliente: document_type, document_number, email
 *   ✓ SÍ exponer al cliente: todo lo demás
 *
 * Solo el vendedor dueño del especialista y el administrador
 * ven los campos sensibles.
 *
 * El campo `google_calendar_id` nunca se expone en ningún contexto
 * (es infraestructura interna).
 */
final class SpecialistResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $user = $request->user();
        $isSeller = $user?->hasRole('seller') || $user?->hasRole('administrator');

        // El vendedor solo ve datos privados de sus PROPIOS especialistas
        $isOwner = $isSeller && (
            $user->ownedStores()->where('stores.id', $this->store_id)->exists()
            || $user->stores()->where('stores.id', $this->store_id)->exists()
        );

        return [
            // ── Identificación pública ────────────────────────────────────
            'id' => $this->id,
            'store_id' => $this->store_id,

            'category_id' => $this->category_id,
            'category_name' => $this->whenLoaded(
                'category',
                fn () => $this->category?->name,
                null
            ),
            'parent_category_id' => $this->whenLoaded(
                'category',
                fn () => $this->category?->parent_id,
                null
            ),
            'parent_category_name' => $this->whenLoaded(
                'category',
                fn () => $this->category?->parent?->name,
                null
            ),

            // ── Datos públicos (visibles al cliente) ──────────────────────
            'nombres' => $this->nombres,
            'apellidos' => $this->apellidos,
            'nombre_completo' => $this->nombre_completo, // accessor del modelo
            'especialidad' => $this->especialidad,
            'sub_especialidad' => $this->sub_especialidad,
            'anios_experiencia' => $this->anios_experiencia,
            'numero_colegiatura' => $this->numero_colegiatura,
            'foto' => $this->foto,
            'availability' => $this->availability,

            // ── Datos privados (solo vendedor dueño o admin) ──────────────
            'document_type' => $this->when($isOwner, $this->document_type),
            'document_number' => $this->when($isOwner, $this->document_number),
            'email' => $this->when($isOwner, $this->email),
            // google_calendar_id: nunca se expone, es infraestructura interna

            // ── Rating (calculado de reseñas vía service_bookings) ────────
            'avg_rating' => round($this->avg_rating, 1),
            'total_ratings' => $this->total_ratings,

            // ── Horarios del especialista (cuando se carga la relación) ───
            // Agrupa los schedules de este especialista por servicio.
            // Solo se incluye si la relación 'schedules' fue cargada con load().
            'schedules' => $this->whenLoaded(
                'schedules',
                fn () => $this->schedules->map(fn ($s) => [
                    'id' => $s->id,
                    'service_id' => $s->service_id,
                    'day_of_week' => $s->day_of_week,
                    'start_time' => $s->start_time,
                    'end_time' => $s->end_time,
                    'orden_bloque' => $s->orden_bloque,
                    'max_appointments' => $s->max_appointments,
                    'is_active' => (bool) $s->is_active,
                ])->values()
            ),

            // ── Timestamps ────────────────────────────────────────────────
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
