@props(['value', 'limit' => 50, 'maxLength' => null])
@php
    $displayValue = $value === null || $maxLength === null ? $value : \Illuminate\Support\Str::limit($value, $maxLength);
@endphp
@if($value === null || $value === '')
    <span class="text-gray-600">—</span>
@elseif(mb_strlen($value) > $limit)
    <details class="journal-detail max-w-sm">
        <summary title="{{ $displayValue }}" class="flex cursor-pointer list-none items-start gap-1.5 text-gray-300 hover:text-white">
            <svg aria-hidden="true" class="mt-1 h-3 w-3 shrink-0 transition-transform" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m9 5 7 7-7 7"/></svg>
            <span class="min-w-0 break-all">{{ \Illuminate\Support\Str::limit($value, $limit) }}</span>
        </summary>
        <p class="mt-2 max-h-64 overflow-y-auto whitespace-pre-wrap break-all rounded-lg bg-gray-950 p-3 text-xs leading-5 text-gray-400">{{ $displayValue }}</p>
    </details>
@else
    <span class="break-all">{{ $value }}</span>
@endif
