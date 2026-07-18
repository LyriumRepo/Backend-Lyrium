<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class ProtectionRule extends Model
{
    protected $fillable = [
        'name',
        'type',
        'pattern',
        'severity',
        'status',
        'priority',
        'description',
        'config',
        'triggered_at',
        'trigger_count',
        'created_by',
    ];

    protected $casts = [
        'config' => 'array',
        'triggered_at' => 'datetime',
        'trigger_count' => 'integer',
        'priority' => 'integer',
    ];

    public const TYPE_RATE_LIMIT = 'rate_limit';
    public const TYPE_IP_BLOCK = 'ip_block';
    public const TYPE_GEO = 'geo';
    public const TYPE_DEVICE = 'device';
    public const TYPE_CUSTOM = 'custom';

    public const STATUS_ACTIVE = 'active';
    public const STATUS_INACTIVE = 'inactive';
    public const STATUS_TRIGGERED = 'triggered';

    public const SEVERITY_LOW = 'info';
    public const SEVERITY_MEDIUM = 'warning';
    public const SEVERITY_HIGH = 'critical';

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function scopeActive($query)
    {
        return $query->where('status', self::STATUS_ACTIVE);
    }

    public function scopeByType($query, string $type)
    {
        return $query->where('type', $type);
    }
}
