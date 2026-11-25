<?php

declare(strict_types=1);

namespace App\Filament\Resources;

use App\Filament\Forms\GuideCodeBlock;
use App\Filament\Forms\GuideExampleBlock;
use App\Filament\Forms\GuideImageBlock;
use App\Filament\Forms\GuideNoteBlock;
use App\Filament\Resources\GuideResource\Pages;
use App\Models\Guide;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

final class GuideResource extends Resource
{
    protected static ?string $model = Guide::class;

    protected static \BackedEnum|string|null $navigationIcon = 'heroicon-o-book-open';

    protected static ?string $navigationLabel = 'Руководства';

    protected static ?string $modelLabel = 'Руководство';

    protected static ?string $pluralModelLabel = 'Руководства';

    protected static ?int $navigationSort = 4;

    public static function form(Schema $form): Schema
    {
        return $form->components([
            Section::make('Статья')->schema([
                TextInput::make('title')->label('Заголовок')->required()->maxLength(255)->columnSpanFull(),
                TextInput::make('slug')->label('Адрес')->required()->maxLength(200)
                    ->regex('/^[a-z0-9]+(?:-[a-z0-9]+)*$/')->unique(ignoreRecord: true)
                    ->disabled(fn (?Guide $record): bool => $record !== null),
                TextInput::make('category')->label('Категория')->required()->maxLength(255),
                TextInput::make('topics')->label('Темы')->required()->maxLength(255)->default('Telegram Bot API'),
                TextInput::make('reading_time')->label('Время чтения')->required()->maxLength(50)->default('5 минут'),
                DatePicker::make('published_at')->label('Дата публикации')->required()->default(now())->native(false),
                Toggle::make('is_published')->label('Опубликована')->default(true),
                TextInput::make('sort_order')->label('Порядок в каталоге')->integer()->minValue(0)->maxValue(1000000)->default(0)->required(),
            ])->columns(2)->columnSpanFull(),
            Section::make('Поиск и карточки')->schema([
                TextInput::make('seo_title')->label('SEO-заголовок')->maxLength(255),
                Textarea::make('description')->label('Описание')->required()->maxLength(2000),
                Textarea::make('card_description')->label('Отдельное описание карточки')->maxLength(2000),
            ])->columns(2)->columnSpanFull(),
            Section::make('Содержание')->schema([
                self::editor('intro')->label('Вступление')->required(),
                self::editor('preamble')->label('Перед оглавленными разделами'),
                Repeater::make('sections')->label('Разделы')->schema([
                    TextInput::make('id')->label('Якорь')->required()->maxLength(100)
                        ->regex('/^[a-z][a-z0-9-]*$/')->distinct(),
                    TextInput::make('label')->label('Подпись в оглавлении')->required()->maxLength(255),
                    self::editor('content')->label('Текст раздела')->required()->columnSpanFull(),
                ])->columns(2)->minItems(1)->maxItems(100)->defaultItems(1)
                    ->collapsible()->itemLabel(fn (array $state): ?string => is_string($state['label'] ?? null) ? $state['label'] : null)
                    ->addActionLabel('Добавить раздел'),
            ])->columnSpanFull(),
        ]);
    }

    private static function editor(string $name): RichEditor
    {
        return RichEditor::make($name)->json()->fileAttachments(false)
            ->default(['type' => 'doc', 'content' => []])
            ->dehydrateStateUsing(fn (?array $state): array => $state ?: ['type' => 'doc', 'content' => []])
            ->toolbarButtons([
                ['bold', 'italic', 'underline', 'link'], ['h2', 'h3'],
                ['bulletList', 'orderedList', 'blockquote', 'table', 'customBlocks'], ['undo', 'redo'],
            ])->customBlocks([GuideCodeBlock::class, GuideNoteBlock::class, GuideImageBlock::class, GuideExampleBlock::class]);
    }

    public static function table(Table $table): Table
    {
        return $table->columns([
            TextColumn::make('title')->label('Заголовок')->searchable()->wrap(),
            TextColumn::make('category')->label('Категория')->searchable(),
            IconColumn::make('is_published')->label('Опубликована')->boolean(),
            TextColumn::make('published_at')->label('Дата')->date('d.m.Y')->sortable(),
            TextColumn::make('sort_order')->label('Порядок')->sortable(),
            TextColumn::make('views_last_30_days')->label('Просмотры за 30 дней')->sortable(),
            TextColumn::make('views_total')->label('Всего')->sortable(),
        ])->filters([
            TernaryFilter::make('is_published')->label('Опубликована'),
        ])->recordActions([EditAction::make()])->defaultSort('sort_order')
            ->modifyQueryUsing(function (Builder $query): void {
                $today = now(config()->string('app.timezone'))->startOfDay();
                $views = DB::table('guide_daily_views')
                    ->select('guide_id')
                    ->selectRaw('SUM(views) AS views_total')
                    ->selectRaw('SUM(CASE WHEN view_date BETWEEN ? AND ? THEN views ELSE 0 END) AS views_last_30_days', [
                        $today->copy()->subDays(29)->toDateString(),
                        $today->toDateString(),
                    ])
                    ->groupBy('guide_id');

                $query->leftJoinSub($views, 'guide_views', 'guide_views.guide_id', '=', 'guides.id')
                    ->addSelect('guides.*')
                    ->selectRaw('COALESCE(guide_views.views_total, 0) AS views_total')
                    ->selectRaw('COALESCE(guide_views.views_last_30_days, 0) AS views_last_30_days');
            });
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListGuides::route('/'),
            'create' => Pages\CreateGuide::route('/create'),
            'edit' => Pages\EditGuide::route('/{record}/edit'),
        ];
    }
}
