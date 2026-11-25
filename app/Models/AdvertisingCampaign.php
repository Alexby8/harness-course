<?php

declare(strict_types=1);

namespace App\Models;

use App\DTO\AdvertisingSource;
use App\Enums\AcquisitionChannel;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Validation\ValidationException;

/**
 * @property AcquisitionChannel $channel
 * @property CarbonImmutable|null $started_on
 * @property CarbonImmutable|null $stopped_on
 */
final class AdvertisingCampaign extends Model
{
    protected $fillable = ['name', 'channel', 'external_id', 'utm_source', 'utm_medium', 'utm_campaign', 'started_on', 'stopped_on', 'includes_vat', 'archived', 'notes'];

    protected $attributes = ['includes_vat' => true, 'archived' => false];

    protected function casts(): array
    {
        return ['channel' => AcquisitionChannel::class, 'started_on' => 'immutable_date', 'stopped_on' => 'immutable_date', 'includes_vat' => 'boolean', 'archived' => 'boolean'];
    }

    protected static function booted(): void
    {
        self::saving(function (self $campaign): void {
            if (! $campaign->channel->isAdvertising()) {
                throw ValidationException::withMessages(['channel' => 'Выберите рекламный источник.']);
            }
            $campaign->utm_source = mb_strtolower(trim($campaign->utm_source));
            $campaign->utm_medium = mb_strtolower(trim($campaign->utm_medium));
            $campaign->utm_campaign = trim($campaign->utm_campaign);
            $campaign->match_key = $campaign->source()->key();
            if (self::query()->where('match_key', $campaign->match_key)->when($campaign->exists, fn ($query) => $query->whereKeyNot($campaign->id))->exists()) {
                throw ValidationException::withMessages(['utm_campaign' => 'Кампания с таким сочетанием источника и UTM уже есть.']);
            }
            if ($campaign->stopped_on !== null && ($campaign->started_on === null || $campaign->stopped_on->lt($campaign->started_on))) {
                throw ValidationException::withMessages(['stopped_on' => 'Дата остановки должна быть не раньше даты запуска.']);
            }
        });
    }

    public function source(): AdvertisingSource
    {
        return new AdvertisingSource($this->channel, mb_strtolower(trim($this->utm_source)), mb_strtolower(trim($this->utm_medium)), trim($this->utm_campaign));
    }

    /** @return HasMany<AdvertisingExpense, $this> */
    public function expenses(): HasMany
    {
        return $this->hasMany(AdvertisingExpense::class);
    }

    public function statusLabel(): string
    {
        return match (true) {
            $this->archived => 'В архиве',
            $this->started_on === null => 'Дата запуска не задана',
            $this->started_on->isFuture() => 'Запланирована',
            $this->stopped_on !== null && $this->stopped_on->lte(today()) => 'Остановлена',
            default => 'Запущена',
        };
    }
}
