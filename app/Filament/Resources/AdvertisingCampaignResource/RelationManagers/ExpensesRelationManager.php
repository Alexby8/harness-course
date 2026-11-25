<?php

declare(strict_types=1);

namespace App\Filament\Resources\AdvertisingCampaignResource\RelationManagers;

use App\DTO\AdvertisingExpenseResult;
use App\Filament\Resources\AdvertisingCampaignResource;
use App\Models\AdvertisingCampaign;
use App\Models\AdvertisingExpense;
use App\Rules\NonOverlappingAdvertisingPeriod;
use App\Services\AdvertisingCampaignPerformance;
use App\Support\AdvertisingMoney;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Actions\RestoreAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\Summarizers\Sum;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use LogicException;

final class ExpensesRelationManager extends RelationManager
{
    protected static string $relationship = 'expenses';

    protected static ?string $title = 'Расходы и результаты по периодам';

    private ?AdvertisingCampaignPerformance $performance = null;

    /** @var array<int, AdvertisingExpenseResult> */
    private array $results = [];

    public function boot(): void
    {
        abort_unless(AdvertisingCampaignResource::canAccess(), 403);
    }

    public static function canViewForRecord(Model $ownerRecord, string $pageClass): bool
    {
        return AdvertisingCampaignResource::canEdit($ownerRecord);
    }

    public function form(Schema $schema): Schema
    {
        return $schema->schema([
            DatePicker::make('period_start')->label('Начало периода расхода')->required()->default(today())->maxDate(today()),
            DatePicker::make('period_end')->label('Конец периода расхода, включительно')->required()->default(today())->maxDate(today())
                ->afterOrEqual('period_start')->rules(fn (Get $get, ?AdvertisingExpense $record): array => [
                    new NonOverlappingAdvertisingPeriod($this->campaign()->id, $get('period_start'), $record?->id),
                ]),
            TextInput::make('amount')->label('Фактический расход, ₽')->required()->numeric()->minValue(0)->maxValue(9_999_999_999.99)
                ->rule('decimal:0,2')->helperText(fn (): string => $this->campaign()->includes_vat ? 'Укажите сумму с НДС.' : 'Укажите сумму без НДС.'),
            TextInput::make('impressions')->label('Показы, необязательно')->numeric()->integer()->minValue(0)->maxValue(999_999_999_999),
            TextInput::make('clicks')->label('Клики, необязательно')->numeric()->integer()->minValue(0)->maxValue(999_999_999_999),
            Textarea::make('notes')->label('Заметки')->maxLength(5000)->columnSpanFull(),
        ]);
    }

    public function table(Table $table): Table
    {
        return $table->description('Расход — за указанные дни. Пользователи — по дате сохранённого рекламного перехода; их регистрация и работа могут произойти позже. Результаты могут пополняться после окончания периода. Стоимость рассчитана только по учтённым пользователям. Подробности — в подсказке колонки «Качество данных».')
            ->modifyQueryUsing(fn (Builder $query) => $query->withoutGlobalScopes([SoftDeletingScope::class]))
            ->columns([
                TextColumn::make('period_start')->label('С')->date('d.m.Y')->sortable(),
                TextColumn::make('period_end')->label('По, включительно')->date('d.m.Y')->sortable(),
                TextColumn::make('amount')->label('Расход')->money('RUB')->summarize(Sum::make()->money('RUB')),
                TextColumn::make('registrations')->label('Регистрации')->getStateUsing(fn (AdvertisingExpense $record): int => $this->result($record)->totals['registered']),
                TextColumn::make('used')->label('Начали работать')->getStateUsing(fn (AdvertisingExpense $record): int => $this->result($record)->totals['used']),
                TextColumn::make('cost_registered')->label('За регистрацию')->getStateUsing(fn (AdvertisingExpense $record): string => $this->result($record)->cost('registered')),
                TextColumn::make('cost_verified')->label('За подтверждение почты')->getStateUsing(fn (AdvertisingExpense $record): string => $this->result($record)->cost('verified'))->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('cost_used')->label('За начавшего работать')->getStateUsing(fn (AdvertisingExpense $record): string => $this->result($record)->cost('used')),
                TextColumn::make('quality')->label('Качество данных')->getStateUsing(fn (AdvertisingExpense $record): string => $this->qualityLabel($record))
                    ->badge()->color('gray')->tooltip(fn (AdvertisingExpense $record): string => $this->quality($record)),
                TextColumn::make('retained')->label('Работали на 8–14-й день')->getStateUsing(fn (AdvertisingExpense $record): int => $this->result($record)->totals['retained'])->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('impressions')->label('Показы')->numeric()->placeholder('—')->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('clicks')->label('Клики')->numeric()->placeholder('—')->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('ctr')->label('CTR')->getStateUsing(fn (AdvertisingExpense $record): string => $record->impressions > 0 && $record->clicks !== null ? round($record->clicks * 100 / $record->impressions, 2).'%' : '—')->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('cpc')->label('За клик')->getStateUsing(fn (AdvertisingExpense $record): string => AdvertisingMoney::perUser($record->kopecks(), $record->clicks ?? 0))->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('notes')->label('Заметки')->limit(80)->toggleable(isToggledHiddenByDefault: true),
            ])->filters([TrashedFilter::make()])->defaultSort('period_start', 'desc')
            ->headerActions([CreateAction::make()->label('Добавить расход')->modalHeading('Добавить расход')->modalSubmitActionLabel('Сохранить')->createAnother(false)->databaseTransaction()])
            ->recordActions([
                EditAction::make()->label('Изменить')->modalHeading('Изменить расход')->modalSubmitActionLabel('Сохранить')->databaseTransaction(),
                DeleteAction::make()->label('Удалить')->modalHeading('Удалить расход?')->databaseTransaction(),
                RestoreAction::make()->label('Восстановить')->modalHeading('Восстановить расход?')->databaseTransaction()
                    ->disabled(fn (AdvertisingExpense $record): bool => AdvertisingExpense::overlapping($record->advertising_campaign_id, $record->period_start->toDateString(), $record->period_end->toDateString(), $record->id)->exists())
                    ->tooltip('Для восстановления период не должен пересекаться с действующими расходами.'),
            ]);
    }

    private function campaign(): AdvertisingCampaign
    {
        $record = $this->getOwnerRecord();
        if (! $record instanceof AdvertisingCampaign) {
            throw new LogicException('Unexpected campaign record.');
        }

        return $record;
    }

    private function result(AdvertisingExpense $expense): AdvertisingExpenseResult
    {
        $this->performance ??= app(AdvertisingCampaignPerformance::class);

        return $this->results[$expense->id] ??= $this->performance->forExpense($expense, $this->campaign());
    }

    private function quality(AdvertisingExpense $expense): string
    {
        $result = $this->result($expense);

        return 'Использование неизвестно: '.$result->totals['usage_unknown'].'; вторая неделя ещё не завершена: '.$result->totals['pending']
            .'; вторая неделя неизвестна: '.$result->totals['retention_unknown'].'; в кампании без даты перехода: '.$result->missingClickDate.'.';
    }

    private function qualityLabel(AdvertisingExpense $expense): string
    {
        $result = $this->result($expense);
        if ($result->missingClickDate > 0 || $result->totals['usage_unknown'] > 0 || $result->totals['retention_unknown'] > 0) {
            return 'Неполные данные';
        }
        if ($expense->period_end->gte(today())) {
            return 'Период ещё идёт';
        }

        return $result->totals['pending'] > 0 ? 'Срок не прошёл' : 'Данные полные';
    }
}
