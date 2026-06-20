<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;

final class TopMedal extends Model
{
    protected $table = 'top_medals';

    protected $fillable = [
        'medalable_type',
        'medalable_id',
        'entity_type',
        'rank_position',
        'status',
        'visible',
        'medal_image_url',
        'times_entered',
        'times_exited',
        'detected_at',
        'approved_at',
        'suspended_at',
        'grace_ends_at',
        'approved_by',
    ];

    protected function casts(): array
    {
        return [
            'rank_position' => 'integer',
            'visible' => 'boolean',
            'times_entered' => 'integer',
            'times_exited' => 'integer',
            'detected_at' => 'datetime',
            'approved_at' => 'datetime',
            'suspended_at' => 'datetime',
            'grace_ends_at' => 'datetime',
            'approved_by' => 'integer',
        ];
    }

    public function medalable(): MorphTo
    {
        return $this->morphTo();
    }

    public function isPending(): bool
    {
        return $this->status === 'pending';
    }

    public function isApproved(): bool
    {
        return $this->status === 'approved';
    }

    public function isSuspended(): bool
    {
        return $this->status === 'suspended';
    }

    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopeApproved($query)
    {
        return $query->where('status', 'approved');
    }

    public function scopeSuspended($query)
    {
        return $query->where('status', 'suspended');
    }

    public function scopeByEntityType($query, string $entityType)
    {
        return $query->where('entity_type', $entityType);
    }
}
