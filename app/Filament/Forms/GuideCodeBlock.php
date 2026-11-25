<?php

declare(strict_types=1);

namespace App\Filament\Forms;

use Filament\Actions\Action;
use Filament\Forms\Components\RichEditor\RichContentCustomBlock;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;

final class GuideCodeBlock extends RichContentCustomBlock
{
    public static function getId(): string
    {
        return 'guide-code';
    }

    public static function getLabel(): string
    {
        return 'Код с подписью';
    }

    public static function configureEditorAction(Action $action): Action
    {
        return $action->schema([
            TextInput::make('label')->label('Подпись')->maxLength(255),
            Textarea::make('code')->label('Код')->required()->rows(16)->maxLength(200000),
        ]);
    }

    public static function toHtml(array $config, array $data): string
    {
        return view('guides.blocks.code', [
            'label' => is_string($config['label'] ?? null) ? $config['label'] : '',
            'code' => is_string($config['code'] ?? null) ? $config['code'] : '',
        ])->render();
    }

    public static function toPreviewHtml(array $config): string
    {
        return '<pre>'.e(is_string($config['code'] ?? null) ? $config['code'] : '').'</pre>';
    }
}
