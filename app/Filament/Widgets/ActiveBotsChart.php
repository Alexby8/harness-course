<?php

declare(strict_types=1);

namespace App\Filament\Widgets;

use App\Enums\BotStatus;
use App\Filament\Widgets\Concerns\HasDayWeekFilter;
use App\Models\Bot;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Facades\Log;

class ActiveBotsChart extends ChartWidget
{
    use HasDayWeekFilter;

    protected ?string $heading = 'Включённые боты';

    protected static ?int $sort = 5;

    protected function getType(): string
    {
        return 'line';
    }

    protected function getData(): array
    {
        Log::debug('[ActiveBotsChart] Building chart data', ['filter' => $this->filter]);

        $periodStart = $this->periodStart();

        $baseline = Bot::query()
            ->where('status', BotStatus::Active)
            ->where('created_at', '<', $periodStart)
            ->count();

        $rows = Bot::query()
            ->selectRaw('DATE(created_at) as date, COUNT(*) as count')
            ->where('status', BotStatus::Active)
            ->where('created_at', '>=', $periodStart)
            ->groupBy('date')
            ->get();

        Log::debug('[ActiveBotsChart] Fetched aggregated rows', ['rows' => $rows->count(), 'baseline' => $baseline]);

        $newPerBucket = $this->bucketByPeriod($rows, 'count');

        $running = $baseline;
        $totals = [];

        foreach ($newPerBucket as $count) {
            $running += $count;
            $totals[] = $running;
        }

        return [
            'datasets' => [
                [
                    'label' => 'Включённые боты',
                    'data' => $totals,
                    'borderColor' => '#22c55e',
                    'backgroundColor' => 'rgba(34, 197, 94, 0.1)',
                    'fill' => true,
                ],
            ],
            'labels' => $this->periodLabels(),
        ];
    }
}
