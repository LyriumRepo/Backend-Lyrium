<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Traits\AuditableModel;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class ForumPost extends Model
{
    use AuditableModel, HasFactory;

    protected $fillable = [
        'store_id',
        'forum_topic_id',
        'user_id',
        'anonymous_name',
        'content',
        'reply_to_id',
        'status',
        'hidden_by',
        'hidden_at',
        'report_count',
        'last_report_at',
    ];

    public function store()
    {
        return $this->belongsTo(Store::class);
    }

    public function topic(): BelongsTo
    {
        return $this->belongsTo(ForumTopic::class, 'forum_topic_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function replyTo(): BelongsTo
    {
        return $this->belongsTo(self::class, 'reply_to_id');
    }
}
