<?php

declare(strict_types=1);

namespace App\Filament\Widgets;

use App\Services\Proxy\ProxyNodeAnalytics;
use Filament\Widgets\ChartWidget;

final class ProxyNodeLatencyChart extends ChartWidget
{
    protected static bool $isDiscovered = false;

    protected static bool $isLazy = false;

    protected ?string $heading = 'Задержка Proxy Nodes';

    protected ?string $description = 'Среднее время ответа health endpoint, мс';

    protected int|string|array $columnSpan = 'full';

    public ?string $filter = '7d';

    protected function getFilters(): array
    {
        return ['24h' => '24 часа', '7d' => '7 дней', '30d' => '30 дней'];
    }

    protected function getType(): string
    {
        return 'line';
    }

    protected function getData(): array
    {
        $chart = app(ProxyNodeAnalytics::class)->chart($this->filter ?? '7d');
        $colors = ['#f59e0b', '#3b82f6', '#22c55e', '#a855f7', '#ef4444'];
        $datasets = [];

        $index = 0;
        foreach ($chart['latency'] as $name => $data) {
            $datasets[] = [
                'label' => $name,
                'data' => $data,
                'borderColor' => $colors[$index % count($colors)],
                'tension' => 0.25,
                'spanGaps' => true,
            ];
            $index++;
        }

        return ['datasets' => $datasets, 'labels' => $chart['labels']];
    }
}
