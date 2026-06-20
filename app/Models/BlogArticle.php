<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

final class BlogArticle extends Model
{
    protected $fillable = [
        'store_id',
        'blog_category_id',
        'title',
        'slug',
        'summary',
        'content',
        'main_image',
        'meta_title',
        'meta_description',
        'keywords',
        'status',
        'is_featured',
        'published_at',
        'views_count',
    ];

    protected function casts(): array
    {
        return [
            'keywords' => 'array',
            'is_featured' => 'boolean',
            'published_at' => 'datetime',
            'views_count' => 'integer',
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
