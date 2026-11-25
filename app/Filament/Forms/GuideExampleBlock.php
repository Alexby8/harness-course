<?php

declare(strict_types=1);

namespace App\Filament\Forms;

use Filament\Actions\Action;
use Filament\Forms\Components\RichEditor\RichContentCustomBlock;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Illuminate\Support\Facades\Validator;

final class GuideExampleBlock extends RichContentCustomBlock
{
    private const URL_PATTERN = '~^https://github\.com/Alexby8/botgate-examples/tree/(?:main|[a-f0-9]{40})/examples/[a-z0-9]+(?:-[a-z0-9]+)*(?:/[a-z0-9]+(?:-[a-z0-9]+)*)?/?$~D';

    public static function getId(): string
    {
        return 'guide-example';
    }

    public static function getLabel(): string
    {
        return 'Пример на GitHub';
    }

    /** @return array<string, list<string>> */
    public static function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:160'],
            'description' => ['required', 'string', 'max:300'],
            'language' => ['required', 'string', 'max:50'],
            'url' => ['required', 'string', 'max:2048', 'url:https', 'regex:'.self::URL_PATTERN],
        ];
    }

    public static function configureEditorAction(Action $action): Action
    {
        return $action->schema([
            TextInput::make('title')->label('Название примера')->required()->maxLength(160),
            Textarea::make('description')->label('Что находится в примере')->required()->maxLength(300)->rows(2),
            TextInput::make('language')->label('Язык')->default('PHP')->required()->maxLength(50),
            TextInput::make('url')->label('Ссылка на пример')->required()->maxLength(2048)
                ->rules(self::rules()['url'])
                ->helperText('Для верхней карточки укажите папку статьи в Alexby8/botgate-examples/tree/main/examples/. Ссылка на конкретный коммит также поддерживается.'),
        ]);
    }

    public static function toHtml(array $config, array $data): string
    {
        if (Validator::make($config, self::rules())->fails()) {
            return '';
        }

        return view('guides.blocks.example', ['example' => $config])->render();
    }

    public static function toPreviewHtml(array $config): string
    {
        return self::toHtml($config, []);
    }
}
