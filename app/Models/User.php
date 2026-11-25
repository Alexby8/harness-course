<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\UserStatus;
use App\Notifications\VerifyEmailNotification;
use Database\Factories\UserFactory;
use Filament\Models\Contracts\FilamentUser;
use Filament\Models\Contracts\HasName;
use Filament\Panel;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\Pivot;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

/** @property UserStatus $status */
class User extends Authenticatable implements FilamentUser, HasName, MustVerifyEmail
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    public function sendEmailVerificationNotification(): void
    {
        $this->notify(new VerifyEmailNotification);
    }

    protected $fillable = [
        'name',
        'email',
        'password',
        'status',
        'api_key_hash',
        'api_key_preview',
        'is_admin',
    ];

    protected $hidden = [
        'password',
        'remember_token',
        'api_key_hash',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'status' => UserStatus::class,
            'is_admin' => 'boolean',
        ];
    }

    public function getFilamentName(): string
    {
        return $this->name ?? $this->email;
    }

    public function canAccessPanel(Panel $panel): bool
    {
        return $this->is_admin;
    }

    public function isActive(): bool
    {
        return $this->status === UserStatus::Active;
    }

    /** @return HasMany<Bot, $this> */
    public function bots(): HasMany
    {
        return $this->hasMany(Bot::class);
    }

    /** @return HasOne<UserAcquisition, $this> */
    public function acquisition(): HasOne
    {
        return $this->hasOne(UserAcquisition::class);
    }

    /** @return BelongsToMany<Notification, $this, Pivot, 'userNotification'> */
    public function notifications(): BelongsToMany
    {
        return $this->belongsToMany(Notification::class, 'user_notifications')
            ->withPivot('is_read', 'created_at')
            ->as('userNotification');
    }

    /** @return BelongsToMany<Notification, $this, Pivot, 'userNotification'> */
    public function activeNotifications(): BelongsToMany
    {
        return $this->notifications()
            ->where(function ($query) {
                $query->whereNull('expires_at')
                    ->orWhere('expires_at', '>', now());
            })
            ->wherePivot('is_read', false)
            ->orderByDesc('notifications.created_at');
    }
}
