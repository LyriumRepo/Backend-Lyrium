<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LiriosTransaction extends Model
{
    protected $fillable = [
        'user_id', 'type', 'amount', 'balance_before', 'balance_after',
        'reference_type', 'reference_id', 'description',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
