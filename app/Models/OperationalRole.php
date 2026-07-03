<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use App\Traits\AuditableModel;
use Illuminate\Database\Eloquent\SoftDeletes;

final class OperationalRole extends Model
{
    use AuditableModel, HasFactory, SoftDeletes;

    protected $fillable = [
        'name',
        'code',
        'description',
        'is_active',
        'modules',
        'requires_2fa',
    ];

    protected $casts = [
        'modules' => 'array',
        'is_active' => 'boolean',
        'requires_2fa' => 'boolean',
    ];

    // ─── Relationships ───────────────────────────────────────────────────────

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'operational_role_user')
            ->withTimestamps();
    }

    // ─── Scopes ──────────────────────────────────────────────────────────────

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}
