<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class CustomerPanelReminder extends Model
{
    const UPDATED_AT = null;

    protected $fillable = [
        'user_id',
        'reminder_day',
    ];

    protected function casts(): array
    {
        return [
            'reminder_day' => 'integer',
            'created_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
