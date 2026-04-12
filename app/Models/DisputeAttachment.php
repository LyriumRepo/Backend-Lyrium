<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class DisputeAttachment extends Model
{
    use HasFactory;

    protected $table = 'dispute_attachments';

    protected $fillable = [
        'dispute_id',
        'message_id',
        'file_path',
        'file_name',
        'mime_type',
        'file_size',
    ];

    protected function casts(): array
    {
        return [
            'file_size' => 'integer',
            'created_at' => 'datetime',
        ];
    }

    public function dispute(): BelongsTo
    {
        return $this->belongsTo(Dispute::class);
    }

    public function message(): BelongsTo
    {
        return $this->belongsTo(DisputeMessage::class, 'message_id');
    }
}
