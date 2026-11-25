<?php

declare(strict_types=1);

namespace App\Filament\Forms;

use Filament\Actions\Action;
use Filament\Forms\Components\RichEditor\RichContentCustomBlock;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Illuminate\Support\Facades\Validator;

final class GuideImageBlock extends RichContentCustomBlock
{
    public const SOURCE_PATTERN = '~^/images/guides/[a-z0-9]+(?:-[a-z0-9]+)*/[a-z0-9]+(?:-[a-z0-9]+)*\.(?:png|jpe?g|webp|avif)$~D';

    public static function getId(): string
    {
        return 'guide-image';
    }

    public static function getLabel(): string
    {
        return 'Иллюстрация';
    }

    /** @return array<string, list<string>> */
    public static function rules(): array
    {
        return [
            'src' => ['required', 'string', 'max:255', 'regex:'.self::SOURCE_PATTERN],
            'alt' => ['required', 'string', 'max:500'],
            'caption' => ['nullable', 'string', 'max:1000'],
            'width' => ['required', 'integer', 'min:1', 'max:8192'],
            'height' => ['required', 'integer', 'min:1', 'max:8192'],
            'mobile_src' => ['nullable', 'required_with:mobile_width,mobile_height', 'string', 'max:255', 'regex:'.self::SOURCE_PATTERN],
            'mobile_width' => ['nullable', 'required_with:mobile_src', 'integer', 'min:1', 'max:8192'],
            'mobile_height' => ['nullable', 'required_with:mobile_src', 'integer', 'min:1', 'max:8192'],
        ];
    }

    public static function configureEditorAction(Action $action): Action
    {
        return $action->schema([
            TextInput::make('src')->label('Путь к изображению')->required()->maxLength(255)
                ->regex(self::SOURCE_PATTERN)->helperText('/images/guides/slug-stati/illustration.webp. Файл выкладывается вместе с сайтом.'),
            Textarea::make('alt')->label('Описание изображения')->required()->maxLength(500)->rows(2),
            Textarea::make('caption')->label('Подпись под изображением')->maxLength(1000)->rows(2),
            TextInput::make('width')->label('Ширина, px')->integer()->minValue(1)->maxValue(8192)->required(),
            TextInput::make('height')->label('Высота, px')->integer()->minValue(1)->maxValue(8192)->required(),
            TextInput::make('mobile_src')->label('Версия для телефона (необязательно)')->maxLength(255)
                ->regex(self::SOURCE_PATTERN)->requiredWith(['mobile_width', 'mobile_height']),
            TextInput::make('mobile_width')->label('Ширина версии для телефона, px')->integer()->minValue(1)->maxValue(8192)->requiredWith('mobile_src'),
            TextInput::make('mobile_height')->label('Высота версии для телефона, px')->integer()->minValue(1)->maxValue(8192)->requiredWith('mobile_src'),
        ]);
    }

    public static function toHtml(array $config, array $data): string
    {
        if (Validator::make($config, self::rules())->fails()) {
            return '';
        }

        return view('guides.blocks.image', ['image' => $config])->render();
    }

    public static function toPreviewHtml(array $config): string
    {
        unset($config['mobile_src'], $config['mobile_width'], $config['mobile_height']);

        return self::toHtml($config, []);
    }
}
