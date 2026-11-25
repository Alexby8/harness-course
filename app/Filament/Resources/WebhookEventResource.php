<?php

declare(strict_types=1);

namespace App\Filament\Resources;

use App\Enums\WebhookEventStatus;
use App\Filament\Resources\WebhookEventResource\Pages;
use App\Models\WebhookEvent;
use Filament\Actions\Action;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Contracts\View\View;

class WebhookEventResource extends Resource
{
    protected static ?string $model = WebhookEvent::class;

    protected static \BackedEnum|string|null $navigationIcon = 'heroicon-o-arrow-path';

    protected static ?string $navigationLabel = 'Webhook события';

    protected static ?string $modelLabel = 'Webhook событие';

    protected static ?string $pluralModelLabel = 'Webhook события';

    protected static ?int $navigationSort = 4;

    public static function form(Schema $form): Schema
    {
        return $form->schema([]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                TextColumn::make('created_at')->label('Дата')->dateTime('d.m.Y H:i:s')->sortable(),
                TextColumn::make('bot.name')->label('Бот')->searchable(),
                TextColumn::make('status')
                    ->label('Статус')
                    ->formatStateUsing(fn (WebhookEventStatus $state): string => $state->label())
                    ->badge()
                    ->color(fn (WebhookEventStatus $state): string => match ($state) {
                        WebhookEventStatus::Sent => 'success',
                        WebhookEventStatus::Failed => 'danger',
                        WebhookEventStatus::Processing => 'warning',
                        WebhookEventStatus::Pending => 'gray',
                    }),
                TextColumn::make('target_url')->label('URL')->limit(40)->searchable()
                    ->color('primary')
                    ->disabledClick(fn (?string $state): bool => blank($state))
                    ->action(Action::make('viewUrl')
                        ->modalHeading('URL доставки webhook')
                        ->modalWidth('3xl')
                        ->modalSubmitAction(false)
                        ->modalCancelActionLabel('Закрыть')
                        ->closeModalByClickingAway(false)
                        ->disabled(fn (WebhookEvent $record): bool => blank($record->target_url))
                        ->modalContent(fn (WebhookEvent $record): View => view('filament.actions.log-text', [
                            'value' => $record->target_url,
                            'label' => 'URL',
                        ]))),
                TextColumn::make('attempts')->label('Попытки'),
                TextColumn::make('last_http_status')->label('HTTP')->placeholder('—'),
                TextColumn::make('last_error')->label('Ошибка')->limit(50)->placeholder('—')
                    ->color('primary')
                    ->disabledClick(fn (?string $state): bool => blank($state))
                    ->action(Action::make('viewError')
                        ->modalHeading('Ошибка доставки webhook')
                        ->modalWidth('3xl')
                        ->modalSubmitAction(false)
                        ->modalCancelActionLabel('Закрыть')
                        ->closeModalByClickingAway(false)
                        ->disabled(fn (WebhookEvent $record): bool => blank($record->last_error))
                        ->modalContent(fn (WebhookEvent $record): View => view('filament.actions.log-text', [
                            'value' => $record->last_error,
                            'label' => 'Ошибка',
                            'maxLength' => 1000,
                        ]))),
                TextColumn::make('delivered_at')->label('Доставлен')->dateTime('d.m.Y H:i')->placeholder('—'),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->label('Статус')
                    ->options(collect(WebhookEventStatus::cases())
                        ->mapWithKeys(fn ($e) => [$e->value => $e->label()])
                        ->all()),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListWebhookEvents::route('/'),
        ];
    }

    public static function canCreate(): bool
    {
        return false;
    }
}
