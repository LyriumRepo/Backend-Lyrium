<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class BlockedIp extends Model
{
    protected $fillable = [
        'ip_address',
        'reason',
        'blocked_by',
        'blocked_at',
        'expires_at',
        'unblocked_at',
        'unblocked_by',
        'status',
    ];

    protected $casts = [
        'blocked_at' => 'datetime',
        'expires_at' => 'datetime',
        'unblocked_at' => 'datetime',
    ];

    public const STATUS_BLOCKED = 'blocked';
    public const STATUS_UNBLOCKED = 'unblocked';
    public const STATUS_FLAGGED = 'flagged';
    public const STATUS_WHITELISTED = 'whitelisted';

    public function blocker(): BelongsTo
    {
        return $this->belongsTo(User::class, 'blocked_by');
    }

    public function unblocker(): BelongsTo
    {
        return $this->belongsTo(User::class, 'unblocked_by');
    }

    public function scopeBlocked($query)
    {
        return $query->where('status', self::STATUS_BLOCKED);
    }

    public function scopeActive($query)
    {
        return $query->where('status', self::STATUS_BLOCKED)
            ->where(fn ($q) => $q->whereNull('expires_at')->orWhere('expires_at', '>', now()));
    }

    public function scopeByIp($query, string $ip)
    {
        return $query->where('ip_address', $ip);
    }
}
