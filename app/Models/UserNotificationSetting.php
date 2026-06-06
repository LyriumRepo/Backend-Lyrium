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
        'push_notifications',
    ];

    protected function casts(): array
    {
        return [
            'email_order' => 'boolean',
            'email_promotions' => 'boolean',
            'email_newsletter' => 'boolean',
            'push_notifications' => 'boolean',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function wantsEmailOrder(): bool
    {
        return $this->email_order ?? true;
    }

    public function wantsEmailPromotions(): bool
    {
        return $this->email_promotions ?? true;
    }

    public function wantsEmailNewsletter(): bool
    {
        return $this->email_newsletter ?? false;
    }

    public function wantsPush(): bool
    {
        return $this->push_notifications ?? true;
    }
}
