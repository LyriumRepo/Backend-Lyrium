<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

final class DisputeMessage extends Model
{
    use HasFactory;

    protected $table = 'dispute_messages';

    protected $fillable = [
        'dispute_id',
        'user_id',
        'message',
        'is_internal',
        'is_system',
    ];

    protected function casts(): array
    {
        return [
            'is_internal' => 'boolean',
            'is_system' => 'boolean',
            'created_at' => 'datetime',
        ];
    }

    public function dispute(): BelongsTo
    {
        return $this->belongsTo(Dispute::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function attachments(): HasMany
    {
        return $this->hasMany(DisputeAttachment::class, 'message_id');
    }
}
