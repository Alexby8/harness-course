<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\DB;

class BotDailyStat extends Model
{
    protected $fillable = [
        'bot_id',
        'date',
        'api_requests_count',
        'api_errors_count',
        'work_api_requests_count',
        'webhook_received_count',
        'webhook_sent_count',
        'webhook_failed_count',
    ];

    // Existing rows remain NULL; only newly observed days have a known zero.
    protected $attributes = ['work_api_requests_count' => 0];

    protected function casts(): array
    {
        return [
            'date' => 'date',
        ];
    }

    /** @return BelongsTo<Bot, $this> */
    public function bot(): BelongsTo
    {
        return $this->belongsTo(Bot::class);
    }

    /**
     * Increment a counter for today, creating the record if it doesn't exist.
     */
    public static function incrementStat(int $botId, string $column, int $amount = 1): void
    {
        $date = now()->startOfDay();

        static::firstOrCreate(
            ['bot_id' => $botId, 'date' => $date],
        )->increment($column, $amount);
    }

    public static function recordWorkApiRequest(int $botId, string $method): void
    {
        if (in_array(strtolower($method), ['getme', 'getwebhookinfo'], true)) {
            return;
        }

        static::query()->where('bot_id', $botId)->where('date', now()->startOfDay())
            ->update(['work_api_requests_count' => DB::raw('COALESCE(work_api_requests_count, 0) + 1')]);
    }
}
