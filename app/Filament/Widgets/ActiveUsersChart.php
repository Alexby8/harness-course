<?php

declare(strict_types=1);

namespace App\Filament\Widgets;

use Carbon\CarbonImmutable;
use Filament\Widgets\ChartWidget;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;

abstract class ActiveUsersChart extends ChartWidget
{
    protected bool $monthly = false;

    /** @var array{labels: list<string>, counts: list<int>}|null */
    private ?array $series = null;

    protected function getType(): string
    {
        return $this->monthly ? 'bar' : 'line';
    }

    public function getDescription(): string
    {
        $series = $this->series();
        $current = $series['counts'][count($series['counts']) - 1] ?? 0;

        return $this->monthly
            ? "В текущем месяце: {$current}. Последние 12 месяцев; текущий месяц ещё не завершён."
            : "Сегодня: {$current}. Последние 30 дней. Успешный API-запрос или входящее событие от Telegram.";
    }

    /** @return array<string, mixed> */
    protected function getData(): array
    {
        $series = $this->series();

        return [
            'datasets' => [[
                'label' => 'Активные пользователи',
                'data' => $series['counts'],
                'borderColor' => '#8b5cf6',
                'backgroundColor' => $this->monthly ? 'rgba(139, 92, 246, 0.5)' : 'rgba(139, 92, 246, 0.1)',
                'fill' => true,
            ]],
            'labels' => $series['labels'],
        ];
    }

    /** @return array<string, mixed> */
    protected function getOptions(): array
    {
        return [
            'scales' => ['y' => ['beginAtZero' => true, 'ticks' => ['precision' => 0]]],
        ];
    }

    /** @return array{labels: list<string>, counts: list<int>} */
    private function series(): array
    {
        if ($this->series !== null) {
            return $this->series;
        }

        $today = CarbonImmutable::today();
        $start = $this->monthly ? $today->startOfMonth()->subMonths(11) : $today->subDays(29);
        $period = $this->monthly ? 'SUBSTR(bot_daily_stats.date, 1, 7)' : 'DATE(bot_daily_stats.date)';

        // Include soft-deleted and disabled bots: their past activity still counts.
        // Deduplicate owners across the entire bucket, never sum daily unique counts.
        $rows = DB::table('bot_daily_stats')
            ->join('bots', 'bots.id', '=', 'bot_daily_stats.bot_id')
            ->where('bot_daily_stats.date', '>=', $start->toDateString())
            ->where('bot_daily_stats.date', '<', $today->addDay()->toDateString())
            ->where(function (Builder $query): void {
                $query->whereColumn('api_requests_count', '>', 'api_errors_count')
                    ->orWhere('webhook_received_count', '>', 0);
            })
            ->selectRaw("{$period} as period, COUNT(DISTINCT bots.user_id) as active_users")
            ->groupByRaw($period)
            ->get();

        $byPeriod = [];
        /** @var object{period: string, active_users: int|string} $row */
        foreach ($rows as $row) {
            $byPeriod[$row->period] = (int) $row->active_users;
        }

        $labels = [];
        $counts = [];
        $steps = $this->monthly ? 12 : 30;

        for ($i = 0; $i < $steps; $i++) {
            $date = $this->monthly ? $start->addMonths($i) : $start->addDays($i);
            $key = $date->format($this->monthly ? 'Y-m' : 'Y-m-d');
            $label = $date->format($this->monthly ? 'm.Y' : 'd.m');
            $labels[] = $this->monthly && $i === $steps - 1 ? $label.' (неполный)' : $label;
            $counts[] = $byPeriod[$key] ?? 0;
        }

        return $this->series = ['labels' => $labels, 'counts' => $counts];
    }
}
