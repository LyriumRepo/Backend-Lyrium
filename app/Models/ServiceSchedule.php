<?php

declare(strict_types=1);

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Log;

/**
 * ServiceSchedule — Bloque horario de un especialista dentro de un servicio.
 *
 * CAMBIOS respecto a la versión anterior:
 *   - specialist_id añadido a $fillable y $casts.
 *   - orden_bloque añadido a $fillable (diferencia bloques del mismo día).
 *   - Relación specialist() → BelongsTo(Specialist::class).
 *   - isAvailableForBooking() ahora filtra también por specialist_id
 *     al contar reservas ocupadas, evitando falsos positivos cuando
 *     dos especialistas comparten servicio pero tienen horarios distintos.
 */
final class ServiceSchedule extends Model
{
    use HasFactory;

    public const DAYS = [
        'monday',
        'tuesday',
        'wednesday',
        'thursday',
        'friday',
        'saturday',
        'sunday',
    ];

    protected $fillable = [
        'service_id',
        'specialist_id',     // ← NUEVO: a qué especialista pertenece este bloque
        'day_of_week',
        'start_time',
        'end_time',
        'orden_bloque',      // ← NUEVO: 1 = mañana, 2 = tarde, etc.
        'max_appointments',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'specialist_id' => 'integer',   // ← NUEVO
            'orden_bloque' => 'integer',   // ← NUEVO
            'start_time' => 'string',
            'end_time' => 'string',
            'max_appointments' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    // ── Mutators: normalizar HH:MM al guardar ─────────────────────────────────

    public function setStartTimeAttribute(mixed $value): void
    {
        $this->attributes['start_time'] = $this->toHHMM($value);
    }

    public function setEndTimeAttribute(mixed $value): void
    {
        $this->attributes['end_time'] = $this->toHHMM($value);
    }

    // ── Accessors: devolver siempre HH:MM ─────────────────────────────────────

    public function getStartTimeAttribute(mixed $value): string
    {
        return $this->toHHMM($value ?? '00:00');
    }

    public function getEndTimeAttribute(mixed $value): string
    {
        return $this->toHHMM($value ?? '00:00');
    }

    private function toHHMM(mixed $value): string
    {
        if ($value instanceof Carbon) {
            return $value->format('H:i');
        }

        $str = trim((string) $value);

        if (preg_match('/^(\d{2}:\d{2})/', $str, $m)) {
            return $m[1];
        }

        try {
            return Carbon::parse($str)->format('H:i');
        } catch (\Throwable) {
            return '00:00';
        }
    }

    // ── Relaciones ────────────────────────────────────────────────────────────

    public function service(): BelongsTo
    {
        return $this->belongsTo(Service::class);
    }

    /**
     * Especialista dueño de este bloque horario.
     * Nullable porque horarios migrados de versiones anteriores
     * pueden no tener specialist_id asignado todavía.
     */
    public function specialist(): BelongsTo
    {
        return $this->belongsTo(Specialist::class);
    }

    public function bookings(): HasMany
    {
        return $this->hasMany(ServiceBooking::class, 'schedule_id');
    }

    // ── Disponibilidad ────────────────────────────────────────────────────────

    /**
     * Verifica si una fecha/hora está disponible para reservar.
     *
     * CAMBIO clave: al contar reservas existentes en el slot, filtra también
     * por specialist_id (si el schedule tiene uno asignado). Esto evita que
     * la agenda del Dr. Juan bloquee slots disponibles de la Dra. María
     * cuando ambos tienen el mismo schedule_id o el mismo slot horario.
     *
     * @param  string  $dateTime  'YYYY-MM-DD HH:MM:SS' o 'YYYY-MM-DD HH:MM'
     */
    public function isAvailableForBooking(string $dateTime): bool
    {
        if (! $this->is_active) {
            Log::warning('BookingValidation: Horario inactivo.', [
                'schedule_id' => $this->id,
            ]);

            return false;
        }

        $date = Carbon::parse($dateTime);

        // ── 1. Verificar día de la semana ──────────────────────────────────
        $dayOfWeek = strtolower($date->format('l'));
        $scheduleDow = $this->normalizeDay(
            $this->getRawOriginal('day_of_week') ?? $this->attributes['day_of_week']
        );

        if ($scheduleDow !== $dayOfWeek) {
            Log::warning('BookingValidation: Día no coincide.', [
                'esperado' => $scheduleDow,
                'recibido' => $dayOfWeek,
            ]);

            return false;
        }

        // ── 2. Verificar rango horario ─────────────────────────────────────
        $slotHHMM = $date->format('H:i');
        $slotTime = Carbon::createFromFormat('H:i', $slotHHMM);
        $startTime = Carbon::createFromFormat('H:i', $this->start_time);
        $endTime = Carbon::createFromFormat('H:i', $this->end_time);

        if ($slotTime->lt($startTime) || $slotTime->gte($endTime)) {
            Log::warning('BookingValidation: Hora fuera del rango.', [
                'rango' => "{$this->start_time} – {$this->end_time}",
                'solicitado' => $slotHHMM,
            ]);

            return false;
        }

        // ── 3. Contar reservas existentes en este slot ─────────────────────
        $this->loadMissing('service');

        $maxPerSlot = (int) (
            $this->service?->settings['max_bookings_per_slot']
            ?? $this->max_appointments
        );

        // Query base: mismo schedule, misma fecha, misma hora, no canceladas
        $bookedQuery = $this->bookings()
            ->whereDate('appointment_date', $date->toDateString())
            ->whereTime('appointment_date', $date->format('H:i:s'))
            ->whereNotIn('status', [ServiceBooking::STATUS_CANCELLED]);

        // Si este bloque tiene specialist_id, contar SOLO sus reservas.
        // Evita que el cupo del Dr. Juan afecte los slots de la Dra. María.
        if ($this->specialist_id) {
            $bookedQuery->where('specialist_id', $this->specialist_id);
        }

        $bookedCount = $bookedQuery->count();

        if ($bookedCount >= $maxPerSlot) {
            Log::warning('BookingValidation: Slot lleno.', [
                'max_permitido' => $maxPerSlot,
                'reservados_actuales' => $bookedCount,
                'specialist_id' => $this->specialist_id,
                'fecha_hora' => $dateTime,
            ]);

            return false;
        }

        return true;
    }

    // ── Helpers privados ──────────────────────────────────────────────────────

    private function normalizeDay(int|string $day): string
    {
        if (is_int($day) || ctype_digit((string) $day)) {
            return self::DAYS[(int) $day] ?? 'monday';
        }

        return strtolower((string) $day);
    }
}
