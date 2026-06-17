<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

final class BlogMedium extends Model
{
    protected $fillable = [
        'store_id',
        'file_path',
        'file_name',
        'file_type',
        'file_size',
        'alt_text',
        'disk',
    ];

    public function store()
    {
        return $this->belongsTo(Store::class);
    }

    public function getUrlAttribute(): string
    {
        return asset("storage/{$this->file_path}");
    }
}
