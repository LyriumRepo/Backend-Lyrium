<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Specialist — Profesional que presta uno o más servicios en una tienda.
 *
 * Campos privados (NUNCA exponer al cliente):
 *   - document_type, document_number, email
 *
 * Campos públicos (visibles al cliente vía SpecialistResource):
 *   - nombres, apellidos, especialidad, sub_especialidad,
 *     anios_experiencia, numero_colegiatura, foto, availability
 *
 * google_calendar_id almacena el email/ID del calendario Google del especialista.
 * Se usa internamente en GoogleCalendarService para crear eventos.
 */
final class Specialist extends Model
{
    use HasFactory;
    use SoftDeletes;

    // ── Disponibilidad ────────────────────────────────────────────────────────
    public const AVAILABILITY_DISPONIBLE  = 'Disponible';
    public const AVAILABILITY_INDISPUESTO = 'Indispuesto';
    public const AVAILABILITY_OCUPADO     = 'Ocupado';     // Se calcula, no se guarda manualmente

    public const AVAILABILITIES = [
        self::AVAILABILITY_DISPONIBLE,
        self::AVAILABILITY_INDISPUESTO,
        self::AVAILABILITY_OCUPADO,
    ];

    // ── Tipos de documento ────────────────────────────────────────────────────
    public const DOCUMENT_TYPES = ['DNI', 'CE', 'PASAPORTE'];

    protected $fillable = [
        'store_id',
        'nombres',
        'apellidos',
        'document_type',
        'document_number',
        'email',                 // Añadido en migración 1 — solo para Google Calendar
        'especialidad',
        'sub_especialidad',      // Añadido en migración 1
        'anios_experiencia',     // Añadido en migración 1
        'numero_colegiatura',    // Añadido en migración 1
        'availability',
        'foto',
        'google_calendar_id',    // Email/ID del calendario Google del especialista
    ];

    protected function casts(): array
    {
        return [
            'anios_experiencia' => 'integer',
            'created_at'        => 'datetime',
            'updated_at'        => 'datetime',
            'deleted_at'        => 'datetime',
        ];
    }

    // ── Relaciones ────────────────────────────────────────────────────────────

    public function store(): BelongsTo
    {
        return $this->belongsTo(Store::class);
    }

    /** Servicios a los que está asignado este especialista (pivot service_specialist). */
    public function services(): BelongsToMany
    {
        return $this->belongsToMany(Service::class, 'service_specialist');
    }

    /** Horarios propios del especialista dentro de cada servicio. */
    public function schedules(): HasMany
    {
        return $this->hasMany(ServiceSchedule::class);
    }

    /** Reservas asignadas a este especialista. */
    public function bookings(): HasMany
    {
        return $this->hasMany(ServiceBooking::class);
    }

    // ── Helpers ───────────────────────────────────────────────────────────────

    public function isDisponible(): bool
    {
        return $this->availability === self::AVAILABILITY_DISPONIBLE;
    }

    public function isIndispuesto(): bool
    {
        return $this->availability === self::AVAILABILITY_INDISPUESTO;
    }

    /** Nombre completo para mostrar. */
    public function getNombreCompletoAttribute(): string
    {
        return trim($this->nombres . ' ' . $this->apellidos);
    }

    /**
     * Verifica si el especialista tiene reservas futuras confirmadas
     * (usado antes de eliminarlo).
     */
    public function hasFutureBookings(): bool
    {
        return $this->bookings()
            ->whereIn('status', [ServiceBooking::STATUS_PENDING, ServiceBooking::STATUS_CONFIRMED])
            ->where('appointment_date', '>', now())
            ->exists();
    }
}
