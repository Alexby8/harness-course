<?php

declare(strict_types=1);

namespace App\Filament\Widgets;

use App\Models\ProxyNode;
use App\Models\ProxyNodeHealthCheck;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

final class ProxyNodeHealthStats extends StatsOverviewWidget
{
    protected static bool $isDiscovered = false;

    protected static bool $isLazy = false;

    protected ?string $heading = 'Последние 24 часа';

    protected function getStats(): array
    {
        $checks = ProxyNodeHealthCheck::query()->where('checked_at', '>=', now()->subDay());
        $total = (clone $checks)->count();
        $successful = (clone $checks)->where('successful', true)->count();
        $failed = $total - $successful;
        $availability = $total > 0 ? round($successful * 100 / $total, 2).'%' : 'Нет данных';
        $averageLatency = (clone $checks)->where('successful', true)->avg('latency_ms');

        return [
            Stat::make('Доступность', $availability)
                ->description("{$successful} успешных / {$failed} неуспешных")
                ->color($failed > 0 ? 'warning' : 'success'),
            Stat::make('Средняя задержка', $averageLatency === null ? 'Нет данных' : round((float) $averageLatency).' мс'),
            Stat::make('Узлы online', ProxyNode::query()->where('status', 'online')->count().' / '.ProxyNode::query()->count())
                ->color('success'),
        ];
    }
}
