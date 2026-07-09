<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class AdminSession extends Model
{
    protected $table = 'sessions';

    public $timestamps = false;

    protected $primaryKey = 'id';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $guarded = [];

    protected $casts = [];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function scopeActive($query, int $minutes = 15)
    {
        return $query->where('last_activity', '>=', now()->subMinutes($minutes)->timestamp);
    }

    public function scopeWithUser($query)
    {
        return $query->whereNotNull('user_id')->where('user_id', '!=', '');
    }

    public function getDeviceAttribute(): string
    {
        $ua = $this->user_agent ?? '';
        if (preg_match('/Windows|Macintosh|Linux|iPhone|iPad|Android/', $ua, $m)) {
            return $m[0];
        }
        return 'Desconocido';
    }

    public function getBrowserAttribute(): string
    {
        $ua = $this->user_agent ?? '';
        if (preg_match('/(Firefox|Chrome|Safari|Edge|Opera)\/\S+/', $ua, $m)) {
            return $m[1];
        }
        return 'Desconocido';
    }
}
