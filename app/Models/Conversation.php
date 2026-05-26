<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

final class Conversation extends Model
{
    use HasFactory;

    protected $fillable = [
        'customer_user_id',
        'store_id',
        'category',
        'subject',
        'status',
        'last_message_at',
    ];

    protected function casts(): array
    {
        return [
            'last_message_at' => 'datetime',
        ];
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'customer_user_id');
    }

    public function store(): BelongsTo
    {
        return $this->belongsTo(Store::class);
    }

    public function messages(): HasMany
    {
        return $this->hasMany(ConversationMessage::class);
    }

    public function latestMessage(): HasMany
    {
        return $this->hasMany(ConversationMessage::class)->latestOfMany();
    }

    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    public function scopeForCustomer($query, int $userId)
    {
        return $query->where('customer_user_id', $userId);
    }

    public function scopeForStore($query, $storeIds)
    {
        if (is_array($storeIds) || $storeIds instanceof \Illuminate\Support\Collection) {
            return $query->whereIn('store_id', $storeIds);
        }
        return $query->where('store_id', $storeIds);
    }

    public function unreadCountFor(int $userId): int
    {
        return $this->messages()
            ->where('sender_id', '!=', $userId)
            ->whereNull('read_at')
            ->count();
    }
}
