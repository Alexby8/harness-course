<?php

declare(strict_types=1);

namespace App\Services;

use App\DTO\AdvertisingReport;
use App\DTO\AdvertisingSource;
use App\DTO\AdvertisingUser;
use App\Models\Bot;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Facades\DB;

final class AdvertisingAnalytics
{
    public function report(int $days = 30): AdvertisingReport
    {
        $end = CarbonImmutable::now();
        $start = $end->startOfDay()->subDays(max(1, min($days, 180)) - 1);

        return $this->build(User::query()->whereBetween('created_at', [$start, $end]), $start, $end);
    }

    /** @param list<int> $ids */
    public function forUserIds(array $ids): AdvertisingReport
    {
        return $this->build(User::query()->whereKey($ids), CarbonImmutable::createFromTimestamp(0), CarbonImmutable::now());
    }

    /** @param Builder<User> $query */
    private function build(Builder $query, CarbonImmutable $start, CarbonImmutable $end): AdvertisingReport
    {
        $query->where('is_admin', false)->where('created_at', '<=', $end);
        $users = (clone $query)->select(['id', 'email', 'created_at', 'email_verified_at'])
            ->with('acquisition')
            ->withCount(['bots as all_bots_count' => /** @param Builder<Bot> $query */ function (Builder $query): void {
                $query->withoutGlobalScope(SoftDeletingScope::class);
            }])->orderByDesc('created_at')->orderByDesc('id')->get();

        // One aggregate query, including disabled/soft-deleted bots. No request payloads or raw logs.
        $activity = DB::table('bot_daily_stats as stats')->join('bots', 'bots.id', '=', 'stats.bot_id')
            ->whereIn('bots.user_id', (clone $query)->select('id'))
            ->where('stats.date', '>=', $start->toDateString())->where('stats.date', '<', $end->addDay()->toDateString())
            ->selectRaw('bots.user_id, stats.date, SUM(COALESCE(stats.work_api_requests_count, 0)) as work_requests, SUM(stats.webhook_sent_count) as delivered')
            ->selectRaw('MAX(CASE WHEN stats.work_api_requests_count IS NULL AND stats.api_requests_count > stats.api_errors_count THEN 1 ELSE 0 END) as uncertain')
            ->groupBy('bots.user_id', 'stats.date')->get()->groupBy('user_id');

        $rows = $users->map(function (User $user) use ($activity, $end): AdvertisingUser {
            $registered = CarbonImmutable::parse($user->created_at);
            $retentionStart = $registered->startOfDay()->addDays(7);
            $retentionEnd = $retentionStart->addWeek();
            $used = false;
            $active = false;
            $retained = false;

            /** @var object{date: string, work_requests: int|string, delivered: int|string, uncertain: int|string} $day */
            foreach ($activity->get($user->id, collect()) as $day) {
                $date = substr($day->date, 0, 10);
                if ($date < $registered->toDateString()) {
                    continue;
                }
                $value = (int) $day->work_requests > 0 || (int) $day->delivered > 0 ? true : ((int) $day->uncertain > 0 ? null : false);
                $used = $this->combine($used, $value);
                if ($date >= $end->startOfDay()->subDays(6)->toDateString()) {
                    $active = $this->combine($active, $value);
                }
                if ($date >= $retentionStart->toDateString() && $date < $retentionEnd->toDateString()) {
                    $retained = $this->combine($retained, $value);
                }
            }

            $botsCount = $user->getAttribute('all_bots_count');

            return new AdvertisingUser(
                $user->id, $user->email, $registered, $user->email_verified_at !== null,
                is_numeric($botsCount) ? (int) $botsCount : 0, AdvertisingSource::fromAcquisition($user->acquisition),
                $used, $active, $end->gte($retentionEnd), $end->gte($retentionEnd) ? $retained : null,
            );
        });

        return new AdvertisingReport($rows->toBase(), $start, $end);
    }

    private function combine(?bool $current, ?bool $value): ?bool
    {
        return $current === true || $value === true ? true : ($current === null || $value === null ? null : false);
    }
}
