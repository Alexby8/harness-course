<?php

declare(strict_types=1);

namespace App\Filament\Resources\ProxyNodeResource\Pages;

use App\Filament\Resources\ProxyNodeResource;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

final class ListProxyNodes extends ListRecords
{
    protected static string $resource = ProxyNodeResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('analytics')
                ->label('Аналитика')
                ->icon('heroicon-o-chart-bar')
                ->url(ProxyNodeResource::getUrl('analytics')),
            CreateAction::make()->label('Добавить Proxy Node'),
        ];
    }
}
