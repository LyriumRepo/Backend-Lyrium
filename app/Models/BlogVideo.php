<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class BlogVideo extends Model
{
    use HasFactory;

    protected $fillable = [
        'store_id',
        'title',
        'description',
        'category',
        'category_label',
        'platform',
        'url',
        'youtube_id',
        'thumbnail',
        'duration',
        'is_published',
        'status',
        'views_count',
        'published_at',
    ];

    protected function casts(): array
    {
        return [
            'is_published' => 'boolean',
            'published_at' => 'datetime',
            'views_count' => 'integer',
            'duration' => 'integer',
        ];
    }

    public function store()
    {
        return $this->belongsTo(Store::class);
    }
}
