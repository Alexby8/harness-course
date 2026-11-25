@php
    $maxLength = $maxLength ?? null;
    $isTruncated = $maxLength !== null && mb_strwidth($value) > $maxLength;
    $displayValue = $isTruncated ? \Illuminate\Support\Str::limit($value, $maxLength) : $value;
@endphp

<div x-data="{ copied: false, copyFailed: false }">
    <p style="margin-bottom: 0.75rem;">
        Выделите нужный фрагмент или скопируйте весь показанный текст кнопкой ниже.
        @if ($isTruncated)
            Показаны первые {{ $maxLength }} символов; текст сокращён.
        @endif
    </p>

    <x-filament::input.wrapper class="fi-fo-textarea">
        <textarea
            x-ref="text"
            aria-label="{{ $label }}"
            readonly
            spellcheck="false"
            rows="12"
            style="min-height: 12rem; max-height: 60vh; resize: vertical; overflow-wrap: anywhere;"
        >{{ $displayValue }}</textarea>
    </x-filament::input.wrapper>

    <div style="margin-top: 0.75rem;">
        <x-filament::button
            type="button"
            icon="heroicon-o-clipboard-document"
            x-on:click="
                copied = false;
                copyFailed = false;
                try {
                    await navigator.clipboard.writeText($refs.text.value);
                    copied = true;
                } catch (error) {
                    $refs.text.focus();
                    $refs.text.select();
                    copyFailed = true;
                }
            "
        >
            Скопировать всё
        </x-filament::button>
        <span x-show="copied" x-cloak role="status">Скопировано</span>
        <span x-show="copyFailed" x-cloak role="status">Нажмите Ctrl+C, чтобы скопировать выделенный текст.</span>
    </div>
</div>
