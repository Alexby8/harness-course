<?php

declare(strict_types=1);

namespace App\Services;

use App\DTO\AdvertisingExpenseResult;
use App\DTO\AdvertisingReport;
use App\DTO\AdvertisingSource;
use App\DTO\AdvertisingUser;
use App\Models\AdvertisingCampaign;
use App\Models\AdvertisingExpense;
use App\Models\UserAcquisition;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;

final class AdvertisingCampaignPerformance
{
    /** @var array<string, array{report: AdvertisingReport, dates: array<int, string>, missing: int}> */
    private array $campaigns = [];

    public function __construct(private readonly AdvertisingAnalytics $analytics) {}

    public function forExpense(AdvertisingExpense $expense, AdvertisingCampaign $campaign): AdvertisingExpenseResult
    {
        $cohort = $this->cohort($campaign);
        $start = $expense->period_start->toDateString();
        $end = $expense->period_end->toDateString();
        $users = $cohort['report']->users->filter(function (AdvertisingUser $user) use ($cohort, $start, $end): bool {
            $date = $cohort['dates'][$user->id] ?? null;

            return $date !== null && $date >= $start && $date <= $end;
        })->values();

        return new AdvertisingExpenseResult($expense->kopecks(), $cohort['report']->summary($users), $cohort['missing']);
    }

    /** @return array{report: AdvertisingReport, dates: array<int, string>, missing: int} */
    private function cohort(AdvertisingCampaign $campaign): array
    {
        if (isset($this->campaigns[$campaign->match_key])) {
            return $this->campaigns[$campaign->match_key];
        }

        // Narrow by campaign in SQL, then compare the complete normalized source tuple in PHP.
        $acquisitions = UserAcquisition::query()->where(function (Builder $query) use ($campaign): void {
            $query->where('last_tagged_touch->parameters->utm_campaign', $campaign->utm_campaign)
                ->orWhere(function (Builder $query) use ($campaign): void {
                    $query->whereNull('last_tagged_touch')->where('first_touch->parameters->utm_campaign', $campaign->utm_campaign);
                });
        })->whereHas('user', fn (Builder $query) => $query->where('is_admin', false))->get();
        $dates = [];
        $missing = 0;
        foreach ($acquisitions as $acquisition) {
            if (AdvertisingSource::fromAcquisition($acquisition)->key() !== $campaign->match_key) {
                continue;
            }
            $touch = $acquisition->getAttribute('last_tagged_touch') ?? $acquisition->getAttribute('first_touch');
            $captured = is_array($touch) ? ($touch['captured_at'] ?? null) : null;
            if (! is_int($captured) || $captured < 0 || $captured > now()->getTimestamp()) {
                $missing++;

                continue;
            }
            $dates[$acquisition->user_id] = CarbonImmutable::createFromTimestamp($captured, config()->string('app.timezone'))->toDateString();
        }

        return $this->campaigns[$campaign->match_key] = [
            'report' => $this->analytics->forUserIds(array_keys($dates)), 'dates' => $dates, 'missing' => $missing,
        ];
    }
}
