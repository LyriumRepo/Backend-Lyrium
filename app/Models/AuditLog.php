<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Http\Request;

final class AuditLog extends Model
{
    // Tabla append-only: nunca se actualiza, nunca se borra
    public $timestamps = false;

    protected $fillable = [
        'user_id',
        'user_email',
        'user_role',
        'session_id',
        'correlation_id',
        'event',
        'module',
        'severity',
        'description',
        'success',
        'source',
        'auditable_type',
        'auditable_id',
        'old_values',
        'new_values',
        'metadata',
        'ip_address',
        'user_agent',
        'request_method',
        'request_url',
        'response_code',
        'created_at',
    ];

    protected $casts = [
        'old_values' => 'array',
        'new_values' => 'array',
        'metadata' => 'array',
        'success' => 'boolean',
        'created_at' => 'datetime',
    ];

    // ─── Relationships ───────────────────────────────────────────────────────

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function auditable(): MorphTo
    {
        return $this->morphTo();
    }

    // ─── Factory method ──────────────────────────────────────────────────────

    /**
     * Registra una entrada de auditoría de forma fluida.
     *
     * Uso:
     * AuditLog::record('created', 'suppliers', "Creó proveedor {$supplier->name}", $supplier);
     */
    public static function record(
        string $event,
        string $module,
        string $description,
        ?Model $auditable = null,
        array $oldValues = [],
        array $newValues = [],
    ): static {

        /** @var \App\Models\User|null $user */
        $user = auth()->user();

        /** @var Request $request */
        $request = request();

        return self::create([
            'user_id' => $user?->id,
            'user_email' => $user?->email,
            // Ahora el editor sabe que $user es App\Models\User y reconoce getRoleNames()
            'user_role' => $user !== null ? (string) ($user->getRoleNames()->first() ?? '') : null,
            'event' => $event,
            'module' => $module,
            'description' => $description,
            'auditable_type' => $auditable ? get_class($auditable) : null,
            'auditable_id' => $auditable?->getKey(),
            'old_values' => $oldValues ?: null,
            'new_values' => $newValues ?: null,
            // Ahora el editor sabe que $request es un objeto Request y no un array
            'ip_address' => $request->ip() !== null ? (string) $request->ip() : null,
            'user_agent' => $request->userAgent() !== null ? (string) $request->userAgent() : null,
            'created_at' => now(),
        ]);
    }

    // ─── Scopes ──────────────────────────────────────────────────────────────

    public function scopeModule($query, string $module)
    {
        return $query->where('module', $module);
    }

    public function scopeEvent($query, string $event)
    {
        return $query->where('event', $event);
    }

    public function scopeForUser($query, int $userId)
    {
        return $query->where('user_id', $userId);
    }

    public function scopeDateRange($query, ?string $from, ?string $to)
    {
        if ($from) {
            $query->whereDate('created_at', '>=', $from);
        }
        if ($to) {
            $query->whereDate('created_at', '<=', $to);
        }

        return $query;
    }
}
