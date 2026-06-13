<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

final class ForumCategory extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'slug',
        'description',
        'topic_count',
        'post_count',
        'is_active',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    public function topics(): HasMany
    {
        return $this->hasMany(ForumTopic::class);
    }

    public function posts(): HasMany
    {
        return $this->hasManyThrough(ForumPost::class, ForumTopic::class);
    }
}
