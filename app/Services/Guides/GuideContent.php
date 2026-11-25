<?php

declare(strict_types=1);

namespace App\Services\Guides;

use App\Filament\Forms\GuideCodeBlock;
use App\Filament\Forms\GuideExampleBlock;
use App\Filament\Forms\GuideImageBlock;
use App\Filament\Forms\GuideNoteBlock;
use Filament\Forms\Components\RichEditor\RichContentRenderer;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

final class GuideContent
{
    /** @param array<string, mixed> $data
     * @return array<string, mixed>
     */
    public function validate(array $data, ?int $id = null): array
    {
        $validated = Validator::make($data, [
            'slug' => ['required', 'string', 'max:200', 'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/', Rule::unique('guides')->ignore($id)],
            'title' => ['required', 'string', 'max:255'],
            'seo_title' => ['nullable', 'string', 'max:255'],
            'description' => ['required', 'string', 'max:2000'],
            'card_description' => ['nullable', 'string', 'max:2000'],
            'category' => ['required', 'string', 'max:255'],
            'topics' => ['required', 'string', 'max:255'],
            'reading_time' => ['required', 'string', 'max:50'],
            'published_at' => ['required', 'date_format:Y-m-d'],
            'is_published' => ['required', 'boolean'],
            'sort_order' => ['required', 'integer', 'min:0', 'max:1000000'],
            'intro' => ['required', 'array'],
            'preamble' => ['required', 'array'],
            'sections' => ['required', 'array', 'min:1', 'max:100'],
            'sections.*' => ['required', 'array:id,label,content'],
            'sections.*.id' => ['required', 'string', 'max:100', 'regex:/^[a-z][a-z0-9-]*$/', 'distinct', Rule::notIn(['content'])],
            'sections.*.label' => ['required', 'string', 'max:255'],
            'sections.*.content' => ['required', 'array'],
        ])->validate();

        $this->validateDocument($validated['intro'], 'intro');
        $this->validateDocument($validated['preamble'], 'preamble');
        foreach ($validated['sections'] as $key => $section) {
            $this->validateDocument($section['content'], "sections.$key.content");
        }

        return $validated;
    }

    private function validateDocument(mixed $document, string $field): void
    {
        if (! is_array($document) || ($document['type'] ?? null) !== 'doc') {
            throw ValidationException::withMessages([$field => 'Ожидается документ редактора.']);
        }
        $this->validateNode($document, $field, 0);
    }

    private function validateNode(mixed $node, string $field, int $depth): void
    {
        $allowed = ['doc', 'paragraph', 'text', 'heading', 'bulletList', 'orderedList', 'listItem', 'blockquote', 'codeBlock', 'hardBreak', 'horizontalRule', 'table', 'tableRow', 'tableCell', 'tableHeader', 'customBlock'];
        if ($depth > 30 || ! is_array($node) || ! in_array($node['type'] ?? null, $allowed, true)) {
            throw ValidationException::withMessages([$field => 'Неподдерживаемый блок содержимого.']);
        }
        if (isset($node['text']) && ! is_string($node['text'])) {
            throw ValidationException::withMessages([$field => 'Текст блока должен быть строкой.']);
        }
        $attributes = $node['attrs'] ?? [];
        if (! is_array($attributes)) {
            throw ValidationException::withMessages([$field => 'Некорректные атрибуты блока.']);
        }
        if ($node['type'] === 'heading' && ! in_array($attributes['level'] ?? null, [2, 3, 4], true)) {
            throw ValidationException::withMessages([$field => 'В тексте допустимы заголовки H2–H4.']);
        }
        if ($node['type'] === 'customBlock') {
            $block = $attributes['id'] ?? null;
            $config = $attributes['config'] ?? null;
            if (! in_array($block, ['guide-code', 'guide-note', 'guide-image', 'guide-example'], true) || ! is_array($config)) {
                throw ValidationException::withMessages([$field => 'Неизвестный блок статьи.']);
            }
            Validator::make($config, match ($block) {
                'guide-code' => ['label' => ['nullable', 'string', 'max:255'], 'code' => ['required', 'string', 'max:200000']],
                'guide-image' => GuideImageBlock::rules(),
                'guide-example' => GuideExampleBlock::rules(),
                default => ['content' => ['required', 'string', 'max:100000'], 'highlighted' => ['required', 'boolean']],
            })->validate();
        }
        if (isset($node['marks'])) {
            if (! is_array($node['marks'])) {
                throw ValidationException::withMessages([$field => 'Некорректное форматирование.']);
            }
            foreach ($node['marks'] as $mark) {
                if (! is_array($mark) || ! in_array($mark['type'] ?? null, ['bold', 'italic', 'underline', 'strike', 'code', 'link', 'subscript', 'superscript'], true)
                    || (isset($mark['attrs']) && ! is_array($mark['attrs']))) {
                    throw ValidationException::withMessages([$field => 'Неподдерживаемое форматирование.']);
                }
            }
        }
        if (isset($node['content'])) {
            if (! is_array($node['content'])) {
                throw ValidationException::withMessages([$field => 'Некорректное содержимое блока.']);
            }
            foreach ($node['content'] as $child) {
                $this->validateNode($child, $field, $depth + 1);
            }
        }
    }

    /** @param array<string, mixed> $document */
    public function render(array $document): string
    {
        if (empty($document['content'])) {
            return '';
        }

        return RichContentRenderer::make($document)
            ->customBlocks([GuideCodeBlock::class, GuideNoteBlock::class, GuideImageBlock::class, GuideExampleBlock::class])
            ->toHtml();
    }
}
