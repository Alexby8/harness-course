<?php

declare(strict_types=1);

namespace App\Filament\Resources;

use App\Enums\UserStatus;
use App\Filament\Resources\UserResource\Pages;
use App\Models\User;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class UserResource extends Resource
{
    protected static ?string $model = User::class;
    protected static \BackedEnum|string|null $navigationIcon = 'heroicon-o-users';
    protected static ?string $navigationLabel = 'Пользователи';
    protected static ?string $modelLabel = 'Пользователь';
    protected static ?string $pluralModelLabel = 'Пользователи';
    protected static ?int $navigationSort = 1;

    public static function form(Schema $form): Schema
    {
        return $form->schema([
            TextInput::make('email')->email()->required()->label('Email'),
            Select::make('status')
                ->label('Статус')
                ->options(collect(UserStatus::cases())->mapWithKeys(fn ($c) => [$c->value => $c->label()]))
                ->required(),
            Toggle::make('is_admin')->label('Администратор'),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->recordUrl(fn (User $record) => static::getUrl('view', ['record' => $record]))
            ->columns([
                TextColumn::make('id')->label('ID')->sortable(),
                TextColumn::make('email')->label('Email')->searchable()->sortable(),
                IconColumn::make('email_verified_at')
                    ->label('Подтвержден')
                    ->boolean()
                    ->getStateUsing(fn (User $record) => $record->email_verified_at !== null),
                TextColumn::make('status')
                    ->label('Статус')
                    ->badge()
                    ->formatStateUsing(fn (UserStatus $state) => $state->label())
                    ->color(fn (UserStatus $state) => $state === UserStatus::Active ? 'success' : 'danger'),
                IconColumn::make('is_admin')->label('Админ')->boolean(),
                TextColumn::make('bots_count')
                    ->label('Боты')
                    ->counts('bots'),
                TextColumn::make('created_at')->label('Создан')->dateTime('d.m.Y H:i')->sortable(),
            ])
            ->recordActions([
                EditAction::make()->label('Изменить'),
                Action::make('disable')
                    ->label('Отключить')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->modalHeading('Отключить пользователя?')
                    ->modalSubmitActionLabel('Отключить')
                    ->visible(fn (User $record) => $record->status === UserStatus::Active)
                    ->action(fn (User $record) => $record->update(['status' => UserStatus::Disabled])),
                Action::make('enable')
                    ->label('Включить')
                    ->color('success')
                    ->visible(fn (User $record) => $record->status === UserStatus::Disabled)
                    ->action(fn (User $record) => $record->update(['status' => UserStatus::Active])),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListUsers::route('/'),
            'view' => Pages\ViewUser::route('/{record}'),
            'edit' => Pages\EditUser::route('/{record}/edit'),
        ];
    }
}
