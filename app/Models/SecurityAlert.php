<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class SecurityAlert extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'audit_log_id',
        'type',
        'title',
        'message',
        'severity',
        'status',
        'ip_address',
        'created_at',
        'resolved_at',
        'resolved_by',
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'resolved_at' => 'datetime',
    ];

    public const STATUS_OPEN = 'open';
    public const STATUS_DISMISSED = 'dismissed';
    public const STATUS_RESOLVED = 'resolved';

    public function auditLog(): BelongsTo
    {
        return $this->belongsTo(AuditLog::class);
    }

    public function resolver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'resolved_by');
    }

    public function scopeOpen($query)
    {
        return $query->where('status', self::STATUS_OPEN);
    }

    public function scopeBySeverity($query, string $severity)
    {
        return $query->where('severity', $severity);
    }
}
