<?php

declare(strict_types=1);

namespace App\Filament\Widgets;

use App\Filament\Pages\Advertising;
use App\Services\AdvertisingAnalytics;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

final class AdvertisingOverview extends StatsOverviewWidget
{
    protected static ?int $sort = 8;

    protected ?string $heading = 'Реклама · регистрации за 30 дней';

    protected ?string $pollingInterval = null;

    /** @return array<Stat> */
    protected function getStats(): array
    {
        $report = app(AdvertisingAnalytics::class)->report();
        $totals = $report->summary($report->select());
        $url = Advertising::getUrl();

        return [
            Stat::make('С рекламы', $totals['registered'])->description('Зарегистрировались · открыть отчёт')->url($url),
            Stat::make('Начали пользоваться', $totals['used'])
                ->description('Неполные данные: '.$totals['usage_unknown'])->url($url),
            Stat::make('Продолжили на 8–14-й день', $totals['retained'])
                ->description('Срок не прошёл: '.$totals['pending'].' · неполные данные: '.$totals['retention_unknown'])->url($url),
        ];
    }
}
