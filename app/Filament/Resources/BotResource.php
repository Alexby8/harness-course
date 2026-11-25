<?php

declare(strict_types=1);

namespace App\Filament\Resources;

use App\Enums\BotStatus;
use App\Filament\Resources\BotResource\Pages;
use App\Models\Bot;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class BotResource extends Resource
{
    protected static ?string $model = Bot::class;
    protected static \BackedEnum|string|null $navigationIcon = 'heroicon-o-cpu-chip';
    protected static ?string $navigationLabel = 'Боты';
    protected static ?string $modelLabel = 'Бот';
    protected static ?string $pluralModelLabel = 'Боты';
    protected static ?int $navigationSort = 2;

    public static function form(Schema $form): Schema
    {
        return $form->schema([
            TextInput::make('name')->label('Название')->required(),
            TextInput::make('public_id')->label('Public ID')->disabled(),
            TextInput::make('telegram_username')->label('Username')->disabled(),
            Select::make('status')
                ->label('Статус')
                ->options(collect(BotStatus::cases())->mapWithKeys(fn ($c) => [$c->value => $c->label()]))
                ->required(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->recordUrl(fn (Bot $record) => static::getUrl('view', ['record' => $record]))
            ->columns([
                TextColumn::make('id')->label('ID')->sortable(),
                TextColumn::make('public_id')->label('Public ID')->searchable(),
                TextColumn::make('user.email')->label('Владелец')->searchable(),
                TextColumn::make('name')->label('Название')->searchable()->sortable(),
                TextColumn::make('telegram_username')->label('Username')
                    ->formatStateUsing(fn ($state) => $state ? '@'.$state : '—'),
                TextColumn::make('status')
                    ->label('Статус')
                    ->badge()
                    ->formatStateUsing(fn (BotStatus $state) => $state->label())
                    ->color(fn (BotStatus $state) => $state === BotStatus::Active ? 'success' : 'danger'),
                IconColumn::make('webhook_enabled')->label('Webhook')->boolean(),
                TextColumn::make('created_at')->label('Создан')->dateTime('d.m.Y H:i')->sortable(),
            ])
            ->recordActions([
                EditAction::make()->label('Изменить'),
                Action::make('disable')
                    ->label('Отключить')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->modalHeading('Отключить бота?')
                    ->modalSubmitActionLabel('Отключить')
                    ->visible(fn (Bot $record) => $record->status === BotStatus::Active)
                    ->action(fn (Bot $record) => $record->update(['status' => BotStatus::Disabled])),
                Action::make('enable')
                    ->label('Включить')
                    ->color('success')
                    ->visible(fn (Bot $record) => $record->status === BotStatus::Disabled)
                    ->action(fn (Bot $record) => $record->update(['status' => BotStatus::Active])),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListBots::route('/'),
            'view' => Pages\ViewBot::route('/{record}'),
            'edit' => Pages\EditBot::route('/{record}/edit'),
        ];
    }
}
