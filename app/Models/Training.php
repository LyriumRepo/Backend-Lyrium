<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

final class Training extends Model
{
    use HasFactory;

    protected $fillable = [
        'title',
        'description',
        'url',
        'platform',
        'thumbnail',
        'category',
        'sort_order',
        'is_required',
        'is_published',
    ];

    protected function casts(): array
    {
        return [
            'is_required' => 'boolean',
            'is_published' => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    public function progress(): HasMany
    {
        return $this->hasMany(TrainingProgress::class);
    }
}
