<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

final class Contract extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'contract_number',
        'store_id',
        'company',
        'ruc',
        'representative',
        'type',
        'modality',
        'status',
        'start_date',
        'end_date',
        'file_path',
        'signed_file_path',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'start_date' => 'date',
            'end_date' => 'date',
        ];
    }

    public function store(): BelongsTo
    {
        return $this->belongsTo(Store::class);
    }

    public function auditTrails(): HasMany
    {
        return $this->hasMany(ContractAuditTrail::class)->orderBy('created_at', 'desc');
    }

    public function addAuditEntry(string $action, string $user): void
    {
        $this->auditTrails()->create([
            'action' => $action,
            'user' => $user,
        ]);
    }

    public function getExpiryUrgencyAttribute(): string
    {
        if (! $this->end_date) {
            return 'normal';
        }

        $now = now();
        $endDate = $this->end_date;

        if ($endDate->isPast()) {
            return 'critical';
        }

        if (abs($endDate->diffInDays($now)) <= 15) {
            return 'warning';
        }

        return 'normal';
    }
}
