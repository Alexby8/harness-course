<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\NotificationType;
use Illuminate\Support\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\Pivot;

/** @property Carbon|null $expires_at */
class Notification extends Model
{
    protected $fillable = [
        'type',
        'title',
        'message',
        'expires_at',
    ];

    protected function casts(): array
    {
        return [
            'type' => NotificationType::class,
            'expires_at' => 'datetime',
        ];
    }

    /** @return BelongsToMany<User, $this, Pivot, 'userNotification'> */
    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'user_notifications')
            ->withPivot('is_read', 'created_at')
            ->as('userNotification');
    }

    public function isExpired(): bool
    {
        return $this->expires_at && $this->expires_at->isPast();
    }

    public function isActive(): bool
    {
        return !$this->isExpired();
    }
}
