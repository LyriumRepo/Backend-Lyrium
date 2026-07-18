<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Traits\AuditableModel;
use Illuminate\Database\Eloquent\Relations\HasMany;

final class ForumTopic extends Model
{
    use AuditableModel, HasFactory;

    protected $fillable = [
        'store_id',
        'forum_category_id',
        'user_id',
        'anonymous_name',
        'title',
        'content',
        'image',
        'status',
        'likes_count',
        'love_count',
        'haha_count',
        'wow_count',
        'sad_count',
        'angry_count',
        'total_reactions',
        'reply_count',
        'views',
    ];

    public function category(): BelongsTo
    {
        return $this->belongsTo(ForumCategory::class, 'forum_category_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function posts(): HasMany
    {
        return $this->hasMany(ForumPost::class, 'forum_topic_id');
    }

    public function store()
    {
        return $this->belongsTo(Store::class);
    }
}
