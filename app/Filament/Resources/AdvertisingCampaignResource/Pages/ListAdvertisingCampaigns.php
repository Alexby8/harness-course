<?php

declare(strict_types=1);

namespace App\Filament\Resources\AdvertisingCampaignResource\Pages;

use App\Filament\Pages\Advertising;
use App\Filament\Resources\AdvertisingCampaignResource;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

final class ListAdvertisingCampaigns extends ListRecords
{
    protected static string $resource = AdvertisingCampaignResource::class;

    protected static ?string $title = 'Рекламные кампании';

    protected function getHeaderActions(): array
    {
        return [
            Action::make('report')->label('Отчёт «Реклама»')->url(Advertising::getUrl())->color('gray'),
            CreateAction::make()->label('Добавить кампанию'),
        ];
    }
}
