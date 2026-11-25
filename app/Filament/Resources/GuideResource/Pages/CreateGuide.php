<?php

declare(strict_types=1);

namespace App\Filament\Resources\GuideResource\Pages;

use App\Filament\Resources\GuideResource;
use Filament\Resources\Pages\CreateRecord;

final class CreateGuide extends CreateRecord
{
    protected static string $resource = GuideResource::class;

    protected static ?string $title = 'Новое руководство';
}
