<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class UserNotificationSetting extends Model
{
    protected $fillable = [
        'user_id',
        'email_order',
        'email_promotions',
        'email_newsletter',
        'sms_order',
        'push_notifications',
    ];

    protected function casts(): array
    {
        return [
            'email_order' => 'boolean',
            'email_promotions' => 'boolean',
            'email_newsletter' => 'boolean',
            'sms_order' => 'boolean',
            'push_notifications' => 'boolean',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
