<?php

declare(strict_types=1);

namespace App\Models;

use App\Support\AdvertisingMoney;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

/**
 * @property CarbonImmutable $period_start
 * @property CarbonImmutable $period_end
 * @property string $amount
 */
final class AdvertisingExpense extends Model
{
    use SoftDeletes;

    protected $fillable = ['period_start', 'period_end', 'amount', 'impressions', 'clicks', 'notes'];

    protected function casts(): array
    {
        return ['period_start' => 'immutable_date', 'period_end' => 'immutable_date', 'amount' => 'decimal:2', 'impressions' => 'integer', 'clicks' => 'integer'];
    }

    /** @return BelongsTo<AdvertisingCampaign, $this> */
    public function campaign(): BelongsTo
    {
        return $this->belongsTo(AdvertisingCampaign::class, 'advertising_campaign_id');
    }

    /** @param array<string, mixed> $options */
    public function save(array $options = []): bool
    {
        return DB::transaction(function () use ($options): bool {
            Validator::make($this->getAttributes(), [
                'amount' => ['required', 'numeric', 'decimal:0,2', 'min:0', 'max:9999999999.99'],
                'period_start' => ['required', 'date', 'before_or_equal:today'],
                'period_end' => ['required', 'date', 'after_or_equal:period_start', 'before_or_equal:today'],
                'impressions' => ['nullable', 'integer', 'min:0', 'max:999999999999'],
                'clicks' => ['nullable', 'integer', 'min:0', 'max:999999999999'],
            ])->validate();
            // Serialize date-range checks for this campaign through the actual insert/update.
            AdvertisingCampaign::query()->whereKey($this->advertising_campaign_id)->lockForUpdate()->firstOrFail();
            if (! $this->trashed() && self::overlapping($this->advertising_campaign_id, $this->period_start->toDateString(), $this->period_end->toDateString(), $this->exists ? $this->id : null)->exists()) {
                throw ValidationException::withMessages(['period_end' => 'Этот период пересекается с уже внесённым расходом. Исправьте существующую запись или выберите другие даты.']);
            }

            return parent::save($options);
        });
    }

    /** @return Builder<self> */
    public static function overlapping(int $campaignId, string $start, string $end, ?int $exceptId = null): Builder
    {
        return self::query()->where('advertising_campaign_id', $campaignId)
            ->whereDate('period_start', '<=', $end)->whereDate('period_end', '>=', $start)
            ->when($exceptId !== null, fn (Builder $query): Builder => $query->whereKeyNot($exceptId));
    }

    public function kopecks(): int
    {
        return AdvertisingMoney::kopecks($this->amount);
    }
}
