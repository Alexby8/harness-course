<?php

declare(strict_types=1);

namespace App\Filament\Resources;

use App\Enums\NotificationType;
use App\Filament\Resources\NotificationResource\Pages;
use App\Models\Notification;
use App\Models\User;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Radio;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class NotificationResource extends Resource
{
    protected static ?string $model = Notification::class;
    protected static \BackedEnum|string|null $navigationIcon = 'heroicon-o-bell';
    protected static ?string $navigationLabel = 'Уведомления';
    protected static ?string $modelLabel = 'Уведомление';
    protected static ?string $pluralModelLabel = 'Уведомления';
    protected static ?int $navigationSort = 3;

    public static function form(Schema $form): Schema
    {
        return $form->schema([
            Select::make('type')
                ->label('Тип')
                ->options(collect(NotificationType::cases())->mapWithKeys(fn ($c) => [$c->value => $c->label()]))
                ->required()
                ->default('info'),
            TextInput::make('title')
                ->label('Заголовок')
                ->required()
                ->maxLength(255),
            Textarea::make('message')
                ->label('Сообщение')
                ->required()
                ->rows(4),
            DateTimePicker::make('expires_at')
                ->label('Дата окончания')
                ->nullable()
                ->native(false)
                ->seconds(false),
            Radio::make('recipients')
                ->label('Получатели')
                ->options([
                    'all' => 'Всем текущим пользователям',
                    'admins' => 'Только администраторам',
                ])
                ->default('all')
                ->visible(fn ($record) => $record === null)
                ->dehydrated(false)
                ->required(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('id')->label('ID')->sortable(),
                TextColumn::make('type')
                    ->label('Тип')
                    ->badge()
                    ->formatStateUsing(fn (NotificationType $state) => $state->label())
                    ->color(fn (NotificationType $state) => match($state) {
                        NotificationType::Info => 'info',
                        NotificationType::Success => 'success',
                        NotificationType::Warning => 'warning',
                        NotificationType::Error => 'danger',
                    }),
                TextColumn::make('title')->label('Заголовок')->searchable()->limit(40),
                TextColumn::make('users_count')
                    ->label('Получатели')
                    ->counts('users'),
                TextColumn::make('expires_at')
                    ->label('Истекает')
                    ->dateTime('d.m.Y H:i')
                    ->sortable()
                    ->placeholder('Без срока'),
                TextColumn::make('created_at')->label('Создано')->dateTime('d.m.Y H:i')->sortable(),
            ])
            ->defaultSort('created_at', 'desc');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListNotifications::route('/'),
            'create' => Pages\CreateNotification::route('/create'),
            'edit' => Pages\EditNotification::route('/{record}/edit'),
        ];
    }
}
