<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

final class Ticket extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'ticket_number',
        'user_id',
        'store_id',
        'assigned_admin_id',
        'subject',
        'description',
        'category',
        'priority',
        'status',
        'is_critical',
        'is_escalated',
        'escalated_to',
        'satisfaction_rating',
        'satisfaction_comment',
        'closed_at',
    ];

    protected function casts(): array
    {
        return [
            'is_critical' => 'boolean',
            'is_escalated' => 'boolean',
            'closed_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function store(): BelongsTo
    {
        return $this->belongsTo(Store::class);
    }

    public function assignedAdmin(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_admin_id');
    }

    public function messages(): HasMany
    {
        return $this->hasMany(TicketMessage::class)->orderBy('created_at');
    }

    public function latestMessage(): HasOne
    {
        return $this->hasOne(TicketMessage::class)->latestOfMany();
    }

    public static function generateTicketNumber(): string
    {
        $year = now()->format('Y');
        $last = self::withTrashed()
            ->where('ticket_number', 'like', "TKT-{$year}-%")
            ->count();

        return sprintf('TKT-%s-%03d', $year, $last + 1);
    }

    public function unreadMessagesFor(int $userId): int
    {
        return $this->messages()
            ->where('user_id', '!=', $userId)
            ->where('is_read', false)
            ->count();
    }
}
