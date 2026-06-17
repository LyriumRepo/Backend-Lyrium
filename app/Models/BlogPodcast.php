<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class BlogPodcast extends Model
{
    use HasFactory;

    protected $fillable = [
        'store_id',
        'type',
        'platform',
        'url',
        'title',
        'description',
        'cover_image',
        'thumbnail',
        'duration',
        'metadata',
        'tags',
        'status',
        'views_count',
        'is_published',
        'published_at',
    ];

    protected function casts(): array
    {
        return [
            'metadata' => 'array',
            'tags' => 'array',
            'is_published' => 'boolean',
            'published_at' => 'datetime',
            'views_count' => 'integer',
        ];
    }

    public function store()
    {
        return $this->belongsTo(Store::class);
    }
}
