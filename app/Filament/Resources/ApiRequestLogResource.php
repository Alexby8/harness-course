<?php

declare(strict_types=1);

namespace App\Filament\Resources;

use App\Filament\Resources\ApiRequestLogResource\Pages;
use App\Models\ApiRequestLog;
use Filament\Actions\Action;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;

class ApiRequestLogResource extends Resource
{
    protected static ?string $model = ApiRequestLog::class;

    protected static \BackedEnum|string|null $navigationIcon = 'heroicon-o-document-text';

    protected static ?string $navigationLabel = 'API Логи';

    protected static ?string $modelLabel = 'API лог';

    protected static ?string $pluralModelLabel = 'API логи';

    protected static ?int $navigationSort = 3;

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
                TextColumn::make('user.email')->label('Пользователь')->searchable(),
                TextColumn::make('method')->label('Метод')->searchable(),
                TextColumn::make('content_type')->label('Тип'),
                TextColumn::make('telegram_http_status')->label('HTTP'),
                TextColumn::make('telegram_ok')
                    ->label('OK')
                    ->state(fn (ApiRequestLog $record): string => match ($record->telegram_ok) {
                        true => 'Да',
                        false => 'Нет',
                        null => 'Нет ответа',
                    })
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'Да' => 'success',
                        'Нет' => 'danger',
                        default => 'warning',
                    }),
                TextColumn::make('duration_ms')->label('мс')->sortable(),
                TextColumn::make('error')->label('Ошибка')->limit(50)->placeholder('—')
                    ->color('primary')
                    ->disabledClick(fn (?string $state): bool => blank($state))
                    ->action(Action::make('viewError')
                        ->modalHeading('Ошибка API запроса')
                        ->modalWidth('3xl')
                        ->modalSubmitAction(false)
                        ->modalCancelActionLabel('Закрыть')
                        ->closeModalByClickingAway(false)
                        ->disabled(fn (ApiRequestLog $record): bool => blank($record->error))
                        ->modalContent(fn (ApiRequestLog $record): View => view('filament.actions.log-text', [
                            'value' => $record->error,
                            'label' => 'Ошибка',
                            'maxLength' => 1000,
                        ]))),
            ])
            ->filters([
                SelectFilter::make('telegram_ok')
                    ->label('Статус')
                    ->options(['1' => 'Успешно', '0' => 'Все ошибки', 'no_response' => 'Нет ответа'])
                    ->query(fn (Builder $query, array $data): Builder => match ($data['value'] ?? null) {
                        '1' => $query->where('telegram_ok', true),
                        '0' => $query->where(fn (Builder $query): Builder => $query->where('telegram_ok', false)->orWhereNull('telegram_ok')),
                        'no_response' => $query->whereNull('telegram_ok'),
                        default => $query,
                    }),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListApiRequestLogs::route('/'),
        ];
    }

    public static function canCreate(): bool
    {
        return false;
    }
}
