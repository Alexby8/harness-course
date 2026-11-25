<?php

declare(strict_types=1);

namespace App\Filament\Resources\BotResource\Pages;

use App\Filament\Resources\BotResource;
use Filament\Resources\Pages\ListRecords;

class ListBots extends ListRecords
{
    protected static string $resource = BotResource::class;
}
