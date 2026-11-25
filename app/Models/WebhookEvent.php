<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\WebhookEventStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** @property WebhookEventStatus $status */
class WebhookEvent extends Model
{
    protected $fillable = [
        'bot_id',
        'telegram_update_id',
        'payload',
        'target_url',
        'status',
        'attempts',
        'last_http_status',
        'last_error',
        'last_attempt_at',
        'delivered_at',
        'queued_at',
    ];

    protected function casts(): array
    {
        return [
            'payload' => 'array',
            'status' => WebhookEventStatus::class,
            'last_attempt_at' => 'datetime',
            'delivered_at' => 'datetime',
            'queued_at' => 'datetime',
        ];
    }

    /** @return BelongsTo<Bot, $this> */
    public function bot(): BelongsTo
    {
        return $this->belongsTo(Bot::class);
    }

    public function hasFailed(): bool
    {
        return $this->status === WebhookEventStatus::Failed;
    }
}
