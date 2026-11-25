<?php

declare(strict_types=1);

namespace App\Filament\Resources\AdvertisingCampaignResource\Pages;

use App\Filament\Resources\AdvertisingCampaignResource;
use Filament\Resources\Pages\EditRecord;

final class EditAdvertisingCampaign extends EditRecord
{
    protected static string $resource = AdvertisingCampaignResource::class;

    protected static ?string $title = 'Кампания и расходы';

    protected ?bool $hasDatabaseTransactions = true;
}
