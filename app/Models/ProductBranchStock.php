<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class ProductBranchStock extends Model
{
    protected $table = 'product_branch_stock';

    public $timestamps = false;

    protected $fillable = [
        'product_id',
        'store_branch_id',
        'stock',
        'pickup_enabled',
    ];

    protected function casts(): array
    {
        return [
            'stock' => 'integer',
            'pickup_enabled' => 'boolean',
        ];
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(StoreBranch::class, 'store_branch_id');
    }
}
