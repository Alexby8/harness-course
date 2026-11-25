<?php

declare(strict_types=1);

namespace App\Filament\Widgets;

use App\Filament\Widgets\Concerns\HasDayWeekFilter;
use App\Models\BotDailyStat;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Facades\Log;

class ApiRequestsChart extends ChartWidget
{
    use HasDayWeekFilter;

    protected ?string $heading = 'API-запросы';

    protected static ?int $sort = 2;

    protected function getType(): string
    {
        return 'line';
    }

    protected function getData(): array
    {
        Log::debug('[ApiRequestsChart] Building chart data', ['filter' => $this->filter]);

        $rows = BotDailyStat::query()
            ->selectRaw('date, SUM(api_requests_count) as requests, SUM(api_errors_count) as errors')
            ->where('date', '>=', $this->periodStart()->toDateString())
            ->groupBy('date')
            ->get();

        Log::debug('[ApiRequestsChart] Fetched aggregated rows', ['rows' => $rows->count()]);

        return [
            'datasets' => [
                [
                    'label' => 'Запросы',
                    'data' => $this->bucketByPeriod($rows, 'requests'),
                    'borderColor' => '#f59e0b',
                    'backgroundColor' => 'rgba(245, 158, 11, 0.1)',
                    'fill' => true,
                ],
                [
                    'label' => 'Ошибки',
                    'data' => $this->bucketByPeriod($rows, 'errors'),
                    'borderColor' => '#ef4444',
                    'backgroundColor' => 'rgba(239, 68, 68, 0.1)',
                    'fill' => true,
                ],
            ],
            'labels' => $this->periodLabels(),
        ];
    }
}
