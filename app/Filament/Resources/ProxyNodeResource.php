<?php

declare(strict_types=1);

namespace App\Filament\Resources;

use App\Enums\ProxyNodeStatus;
use App\Filament\Resources\ProxyNodeResource\Pages;
use App\Models\ProxyNode;
use App\Services\Proxy\ProxyNodeHealthChecker;
use Closure;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Validation\Rule;

final class ProxyNodeResource extends Resource
{
    protected static ?string $model = ProxyNode::class;

    protected static \BackedEnum|string|null $navigationIcon = 'heroicon-o-server-stack';

    protected static ?string $navigationLabel = 'Proxy Nodes';

    protected static ?string $modelLabel = 'Proxy Node';

    protected static ?string $pluralModelLabel = 'Proxy Nodes';

    protected static ?int $navigationSort = 4;

    public static function form(Schema $schema): Schema
    {
        return $schema->schema([
            TextInput::make('node_id')
                ->label('NODE_ID')
                ->required()
                ->maxLength(64)
                ->regex('/^[A-Za-z0-9][A-Za-z0-9._-]{0,63}$/')
                ->rules(fn (?ProxyNode $record): array => [Rule::unique('proxy_nodes', 'node_id')->ignore($record)]),
            TextInput::make('name')
                ->label('Название')
                ->required()
                ->maxLength(255),
            TextInput::make('url')
                ->label('HTTPS origin')
                ->required()
                ->maxLength(500)
                ->rules(fn (?ProxyNode $record): array => [
                    Rule::unique('proxy_nodes', 'url')->ignore($record),
                    self::httpsOriginRule(),
                ]),
            TextInput::make('secret_encrypted')
                ->label('NODE_SECRET')
                ->helperText('При редактировании оставьте пустым, чтобы сохранить текущий секрет.')
                ->password()
                ->required(fn (string $operation): bool => $operation === 'create')
                ->minLength(32)
                ->maxLength(4096)
                ->afterStateHydrated(fn (TextInput $component) => $component->state(null))
                ->dehydrated(fn (?string $state): bool => filled($state)),
            TextInput::make('priority')
                ->label('Приоритет')
                ->helperText('Меньшее число выбирается раньше.')
                ->numeric()
                ->integer()
                ->minValue(0)
                ->maxValue(65_535)
                ->default(100)
                ->required(),
            Toggle::make('enabled')
                ->label('Включен для маршрутизации')
                ->default(false),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')->label('Название')->searchable()->sortable(),
                TextColumn::make('node_id')->label('NODE_ID')->searchable()->copyable(),
                TextColumn::make('status')
                    ->label('Узел')
                    ->badge()
                    ->formatStateUsing(fn (ProxyNodeStatus $state): string => $state->label())
                    ->color(fn (ProxyNodeStatus $state): string => match ($state) {
                        ProxyNodeStatus::Online => 'success',
                        ProxyNodeStatus::Offline => 'danger',
                        ProxyNodeStatus::Unknown => 'gray',
                    }),
                IconColumn::make('enabled')->label('Включен')->boolean(),
                TextColumn::make('priority')->label('Приоритет')->sortable(),
                TextColumn::make('response_time_ms')
                    ->label('Ответ')
                    ->suffix(' мс')
                    ->placeholder('—'),
                TextColumn::make('version')->label('Версия')->placeholder('—'),
                TextColumn::make('last_check_at')
                    ->label('Проверен')
                    ->dateTime('d.m.Y H:i:s')
                    ->sortable()
                    ->placeholder('Никогда'),
            ])
            ->defaultSort('priority')
            ->recordActions([
                Action::make('check')
                    ->label('Проверить')
                    ->icon('heroicon-o-arrow-path')
                    ->action(function (ProxyNode $record): void {
                        $result = app(ProxyNodeHealthChecker::class)->check($record);

                        Notification::make()
                            ->title($result->successful ? 'Proxy Node доступен' : 'Proxy Node недоступен')
                            ->body($result->successful ? "Ответ за {$result->responseTimeMs} мс" : $result->error)
                            ->color($result->successful ? 'success' : 'danger')
                            ->send();
                    }),
                Action::make('enable')
                    ->label('Включить')
                    ->color('success')
                    ->requiresConfirmation()
                    ->visible(fn (ProxyNode $record): bool => ! $record->enabled)
                    ->action(fn (ProxyNode $record) => $record->update(['enabled' => true])),
                Action::make('disable')
                    ->label('Отключить')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->visible(fn (ProxyNode $record): bool => $record->enabled)
                    ->action(fn (ProxyNode $record) => $record->update(['enabled' => false])),
                EditAction::make()->label('Изменить'),
            ]);
    }

    /** @return Closure(string, mixed, Closure): void */
    private static function httpsOriginRule(): Closure
    {
        return function (string $attribute, mixed $value, Closure $fail): void {
            $parts = is_string($value) ? parse_url(rtrim($value, '/')) : false;

            if (
                $parts === false
                || ($parts['scheme'] ?? null) !== 'https'
                || empty($parts['host'])
                || ! empty($parts['user'])
                || ! empty($parts['pass'])
                || ! empty($parts['query'])
                || ! empty($parts['fragment'])
                || ! in_array($parts['path'] ?? '', ['', '/'], true)
            ) {
                $fail('Укажите чистый HTTPS origin без credentials, path, query или fragment.');
            }
        };
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListProxyNodes::route('/'),
            'analytics' => Pages\ProxyNodeAnalytics::route('/analytics'),
            'create' => Pages\CreateProxyNode::route('/create'),
            'edit' => Pages\EditProxyNode::route('/{record}/edit'),
        ];
    }
}
