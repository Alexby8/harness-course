@props(['status', 'label'])
@php
    $color = match ($status) {
        'success', 'sent' => 'bg-green-500/10 text-green-300 ring-green-500/20',
        'error', 'failed' => 'bg-red-500/10 text-red-300 ring-red-500/20',
        'no_response', 'processing' => 'bg-amber-500/10 text-amber-300 ring-amber-500/20',
        default => 'bg-gray-500/10 text-gray-300 ring-gray-500/20',
    };
@endphp
<span class="inline-flex whitespace-nowrap rounded-md px-2 py-1 text-xs font-medium ring-1 ring-inset {{ $color }}">{{ $label }}</span>
