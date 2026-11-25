<?php

declare(strict_types=1);

namespace App\Filament\Resources\GuideResource\Pages;

use App\Filament\Resources\GuideResource;
use App\Models\Guide;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

final class EditGuide extends EditRecord
{
    protected static string $resource = GuideResource::class;

    protected static ?string $title = 'Редактирование руководства';

    protected function getHeaderActions(): array
    {
        return [
            Action::make('open')->label('Открыть статью')->icon('heroicon-o-arrow-top-right-on-square')
                ->url(fn (Guide $record): string => $record->url())->openUrlInNewTab()
                ->visible(fn (Guide $record): bool => $record->is_published),
            DeleteAction::make(),
        ];
    }
}
