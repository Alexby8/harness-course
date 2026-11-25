<?php

declare(strict_types=1);

namespace App\Filament\Resources\ProxyNodeResource\Pages;

use App\Filament\Resources\ProxyNodeResource;
use App\Filament\Widgets\ProxyNodeAvailabilityChart;
use App\Filament\Widgets\ProxyNodeHealthStats;
use App\Filament\Widgets\ProxyNodeLatencyChart;
use Filament\Resources\Pages\Page;

final class ProxyNodeAnalytics extends Page
{
    protected static string $resource = ProxyNodeResource::class;

    protected static ?string $title = 'Аналитика Proxy Nodes';

    protected static ?string $breadcrumb = 'Аналитика';

    protected function getHeaderWidgets(): array
    {
        return [
            ProxyNodeHealthStats::class,
            ProxyNodeAvailabilityChart::class,
            ProxyNodeLatencyChart::class,
        ];
    }

    public function getHeaderWidgetsColumns(): int
    {
        return 1;
    }
}
