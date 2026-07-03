<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

final class AuditLogSummary extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'date',
        'module',
        'severity',
        'total',
    ];

    protected $casts = [
        'date' => 'date:Y-m-d',
        'total' => 'integer',
        'created_at' => 'datetime',
    ];
}
