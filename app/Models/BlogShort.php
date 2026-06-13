<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

final class BlogShort extends Model
{
    protected $fillable = [
        'store_id',
        'blog_category_id',
        'platform',
        'url',
        'title',
        'description',
        'thumbnail',
        'duration',
        'metadata',
        'status',
        'published_at',
        'views_count',
    ];

    protected function casts(): array
    {
        return [
            'metadata' => 'array',
            'published_at' => 'datetime',
            'views_count' => 'integer',
            'duration' => 'integer',
        ];
    }

    public function store()
    {
        return $this->belongsTo(Store::class);
    }

    public function category()
    {
        return $this->belongsTo(BlogCategory::class, 'blog_category_id');
    }
}
