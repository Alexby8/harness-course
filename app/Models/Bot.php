<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\BotStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/** @property BotStatus $status */
class Bot extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'user_id',
        'public_id',
        'name',
        'telegram_token_encrypted',
        'telegram_token_hash',
        'telegram_bot_id',
        'telegram_username',
        'telegram_first_name',
        'webhook_url',
        'webhook_secret',
        'webhook_enabled',
        'status',
        'last_webhook_delivery_at',
        'last_webhook_error',
    ];

    protected $hidden = [
        'telegram_token_encrypted',
        'telegram_token_hash',
        'webhook_secret',
    ];

    protected function casts(): array
    {
        return [
            'telegram_token_encrypted' => 'encrypted',
            'webhook_enabled' => 'boolean',
            'status' => BotStatus::class,
            'last_webhook_delivery_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function webhookEvents(): HasMany
    {
        return $this->hasMany(WebhookEvent::class);
    }

    public function apiRequestLogs(): HasMany
    {
        return $this->hasMany(ApiRequestLog::class);
    }

    public function dailyStats(): HasMany
    {
        return $this->hasMany(BotDailyStat::class);
    }

    public function isActive(): bool
    {
        return $this->status === BotStatus::Active;
    }

    /**
     * Returns masked token for display: 123456789:AAH***
     */
    public function getMaskedTokenAttribute(): string
    {
        $token = $this->telegram_token_encrypted;
        $colonPos = strpos($token, ':');

        if ($colonPos === false) {
            return '***';
        }

        $botId = substr($token, 0, $colonPos);

        return $botId . ':AAH' . str_repeat('*', 15);
    }
}
