<?php

declare(strict_types=1);

namespace App\Filament\Forms;

use Filament\Actions\Action;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\RichEditor\RichContentCustomBlock;
use Filament\Forms\Components\Toggle;
use Illuminate\Support\Str;

final class GuideNoteBlock extends RichContentCustomBlock
{
    public static function getId(): string
    {
        return 'guide-note';
    }

    public static function getLabel(): string
    {
        return 'Примечание';
    }

    public static function configureEditorAction(Action $action): Action
    {
        return $action->schema([
            RichEditor::make('content')->label('Текст')->required()->fileAttachments(false)
                ->toolbarButtons(['bold', 'italic', 'link', 'bulletList', 'orderedList', 'undo', 'redo']),
            Toggle::make('highlighted')->label('Выделить рамкой')->default(false),
        ]);
    }

    public static function toHtml(array $config, array $data): string
    {
        return view('guides.blocks.note', [
            'html' => Str::sanitizeHtml(is_string($config['content'] ?? null) ? $config['content'] : ''),
            'highlighted' => (bool) ($config['highlighted'] ?? false),
        ])->render();
    }

    public static function toPreviewHtml(array $config): string
    {
        return Str::sanitizeHtml(is_string($config['content'] ?? null) ? $config['content'] : '');
    }
}
