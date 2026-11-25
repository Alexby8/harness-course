<?php

declare(strict_types=1);

namespace App\Filament\Widgets\Concerns;

use Carbon\Carbon;
use Illuminate\Support\Collection;

/**
 * Shared day/week period filter + bucketing logic for chart widgets that
 * aggregate a "date" column (either raw DATE(created_at) or a stored date
 * field like bot_daily_stats.date) into 30 daily or 12 weekly buckets.
 */
trait HasDayWeekFilter
{
    public function mount(): void
    {
        $this->filter ??= 'day';

        parent::mount();
    }

    protected function getFilters(): ?array
    {
        return [
            'day' => 'По дням (30 дней)',
            'week' => 'По неделям (12 недель)',
        ];
    }

    protected function periodStart(): Carbon
    {
        return $this->filter === 'week'
            ? now()->subWeeks(11)->startOfWeek()
            : now()->subDays(29)->startOfDay();
    }

    protected function periodSteps(): int
    {
        return $this->filter === 'week' ? 12 : 30;
    }

    /**
     * @return array<int, string>
     */
    protected function periodLabels(): array
    {
        $start = $this->periodStart();
        $steps = $this->periodSteps();
        $isWeekly = $this->filter === 'week';

        $labels = [];

        for ($i = 0; $i < $steps; $i++) {
            $labels[] = $isWeekly
                ? $start->copy()->addWeeks($i)->format('d.m')
                : $start->copy()->addDays($i)->format('d.m');
        }

        return $labels;
    }

    /**
     * Buckets rows (each exposing a `date` attribute and the given numeric
     * column) into the current period's daily or weekly buckets.
     *
     * @template TRow of object
     *
     * @param  Collection<int, TRow>  $rows
     * @return array<int, int>
     */
    protected function bucketByPeriod(Collection $rows, string $column): array
    {
        $start = $this->periodStart();
        $steps = $this->periodSteps();
        $isWeekly = $this->filter === 'week';
        $buckets = array_fill(0, $steps, 0);

        foreach ($rows as $row) {
            $date = Carbon::parse($row->date);
            $index = $isWeekly
                ? (int) floor($start->diffInDays($date) / 7)
                : (int) $start->diffInDays($date);

            if ($index >= 0 && $index < $steps) {
                $buckets[$index] += (int) $row->{$column};
            }
        }

        return $buckets;
    }
}
