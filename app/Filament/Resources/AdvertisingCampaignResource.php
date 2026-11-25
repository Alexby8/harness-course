<?php

declare(strict_types=1);

namespace App\Filament\Resources;

use App\DTO\AdvertisingSource;
use App\Enums\AcquisitionChannel;
use App\Filament\Resources\AdvertisingCampaignResource\Pages;
use App\Filament\Resources\AdvertisingCampaignResource\RelationManagers\ExpensesRelationManager;
use App\Models\AdvertisingCampaign;
use Closure;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

final class AdvertisingCampaignResource extends Resource
{
    protected static ?string $model = AdvertisingCampaign::class;

    protected static ?string $modelLabel = 'Рекламная кампания';

    protected static ?string $pluralModelLabel = 'Рекламные кампании';

    protected static bool $shouldRegisterNavigation = false;

    public static function form(Schema $schema): Schema
    {
        return $schema->schema([
            Section::make('Кампания')->schema([
                TextInput::make('name')->label('Название')->required()->maxLength(255),
                Select::make('channel')->label('Рекламная система')->options([
                    AcquisitionChannel::YandexDirect->value => 'Яндекс.Директ',
                    AcquisitionChannel::OtherAdvertising->value => 'Другая реклама',
                ])->default(AcquisitionChannel::YandexDirect->value)->required(),
                TextInput::make('external_id')->label('Номер кампании в рекламной системе')->maxLength(64)
                    ->helperText('Для справки. Пользователи сопоставляются по UTM ниже, а не по этому номеру.'),
                DatePicker::make('started_on')->label('Дата запуска')->requiredWith('stopped_on'),
                DatePicker::make('stopped_on')->label('Дата остановки')->afterOrEqual('started_on'),
                Toggle::make('includes_vat')->label('Вносимые расходы включают НДС')->default(true)
                    ->helperText('Все суммы кампании должны быть на одной основе. Настройка меняет подпись, налог не пересчитывается.'),
                Toggle::make('archived')->label('В архиве')->default(false)
                    ->helperText('Пометка для списка. История расходов и результатов сохраняется.'),
                Textarea::make('notes')->label('Заметки')->maxLength(5000)->columnSpanFull(),
            ])->columns(2)->columnSpanFull(),
            Section::make('Связь с пользователями')->description('Укажите значения из рекламных ссылок. Регистр utm_campaign имеет значение. Изменение меток изменит подбор пользователей для всех расходов этой карточки.')->schema([
                TextInput::make('utm_source')->label('UTM source')->default('yandex')->maxLength(64)->dehydrateStateUsing(fn (?string $state): string => $state ?? '')
                    ->helperText('Если этой метки в переходах не было, оставьте пустым.'),
                TextInput::make('utm_medium')->label('UTM medium')->default('cpc')->maxLength(32)->dehydrateStateUsing(fn (?string $state): string => $state ?? ''),
                TextInput::make('utm_campaign')->label('UTM campaign')->required()->maxLength(96)
                    ->rules(fn (Get $get, ?AdvertisingCampaign $record): array => [
                        function (string $attribute, mixed $value, Closure $fail) use ($get, $record): void {
                            $channel = $get('channel');
                            $source = $get('utm_source');
                            $medium = $get('utm_medium');
                            if (! is_string($channel) || ! is_string($value)) {
                                return;
                            }
                            $channel = AcquisitionChannel::tryFrom($channel);
                            if ($channel === null || ! $channel->isAdvertising()) {
                                return;
                            }
                            $key = new AdvertisingSource($channel, is_string($source) ? mb_strtolower(trim($source)) : '', is_string($medium) ? mb_strtolower(trim($medium)) : '', trim($value));
                            if (AdvertisingCampaign::query()->where('match_key', $key->key())->when($record !== null, fn ($query) => $query->whereKeyNot($record?->id))->exists()) {
                                $fail('Кампания с таким сочетанием источника и UTM уже есть.');
                            }
                        },
                    ])
                    ->helperText('Если в ссылках utm_campaign={campaign_id}, здесь нужен номер кампании. Одинаковые метки нельзя привязать к двум карточкам.'),
            ])->columns(3)->columnSpanFull(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table->columns([
            TextColumn::make('name')->label('Кампания')->searchable()->sortable(),
            TextColumn::make('channel')->label('Система')->formatStateUsing(fn (AcquisitionChannel $state): string => $state->label()),
            TextColumn::make('external_id')->label('Номер')->searchable()->placeholder('—'),
            TextColumn::make('utm_campaign')->label('UTM campaign')->searchable()
                ->description(fn (AdvertisingCampaign $record): string => $record->utm_source.' / '.$record->utm_medium),
            TextColumn::make('started_on')->label('Запуск')->date('d.m.Y')->placeholder('—'),
            TextColumn::make('stopped_on')->label('Остановка')->date('d.m.Y')->placeholder('—'),
            TextColumn::make('state')->label('Состояние')->getStateUsing(fn (AdvertisingCampaign $record): string => $record->statusLabel()),
            TextColumn::make('expenses_sum_amount')->label('Внесённые расходы')->sum('expenses', 'amount')->money('RUB')
                ->description(fn (AdvertisingCampaign $record): string => $record->includes_vat ? 'С НДС' : 'Без НДС'),
        ])->defaultSort('id', 'desc')->recordActions([EditAction::make()->label('Открыть')]);
    }

    public static function getRelations(): array
    {
        return [ExpensesRelationManager::class];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListAdvertisingCampaigns::route('/'),
            'create' => Pages\CreateAdvertisingCampaign::route('/create'),
            'edit' => Pages\EditAdvertisingCampaign::route('/{record}/edit'),
        ];
    }
}
