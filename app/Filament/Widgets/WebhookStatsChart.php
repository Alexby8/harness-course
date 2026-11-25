<?php

declare(strict_types=1);

namespace App\Filament\Widgets;

use App\Filament\Widgets\Concerns\HasDayWeekFilter;
use App\Models\BotDailyStat;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Facades\Log;

class WebhookStatsChart extends ChartWidget
{
    use HasDayWeekFilter;

    protected ?string $heading = 'Webhook';

    protected static ?int $sort = 3;

    protected function getType(): string
    {
        return 'line';
    }

    protected function getData(): array
    {
        Log::debug('[WebhookStatsChart] Building chart data', ['filter' => $this->filter]);

        $rows = BotDailyStat::query()
            ->selectRaw('date, SUM(webhook_received_count) as received, SUM(webhook_sent_count) as sent, SUM(webhook_failed_count) as failed')
            ->where('date', '>=', $this->periodStart()->toDateString())
            ->groupBy('date')
            ->get();

        Log::debug('[WebhookStatsChart] Fetched aggregated rows', ['rows' => $rows->count()]);

        return [
            'datasets' => [
                [
                    'label' => 'Получено',
                    'data' => $this->bucketByPeriod($rows, 'received'),
                    'borderColor' => '#f59e0b',
                    'backgroundColor' => 'rgba(245, 158, 11, 0.1)',
                    'fill' => true,
                ],
                [
                    'label' => 'Отправлено',
                    'data' => $this->bucketByPeriod($rows, 'sent'),
                    'borderColor' => '#22c55e',
                    'backgroundColor' => 'rgba(34, 197, 94, 0.1)',
                    'fill' => true,
                ],
                [
                    'label' => 'Ошибки',
                    'data' => $this->bucketByPeriod($rows, 'failed'),
                    'borderColor' => '#ef4444',
                    'backgroundColor' => 'rgba(239, 68, 68, 0.1)',
                    'fill' => true,
                ],
            ],
            'labels' => $this->periodLabels(),
        ];
    }
}
