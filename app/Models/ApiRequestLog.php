<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ApiRequestLog extends Model
{
    public const UPDATED_AT = null;

    protected $fillable = [
        'user_id',
        'bot_id',
        'method',
        'content_type',
        'telegram_http_status',
        'telegram_ok',
        'error',
        'duration_ms',
        'ip_address',
    ];

    protected function casts(): array
    {
        return [
            'telegram_ok' => 'boolean',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function bot(): BelongsTo
    {
        return $this->belongsTo(Bot::class);
    }
}
