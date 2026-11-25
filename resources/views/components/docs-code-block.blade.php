@props(['label' => null])

<div class="rounded-lg overflow-hidden border border-gray-800">
    @if($label)
        <div class="bg-gray-800/60 px-4 py-1.5 text-xs text-gray-500 font-mono border-b border-gray-800">
            {{ $label }}
        </div>
    @endif
    <pre class="bg-gray-900 px-4 py-4 text-sm text-gray-300 font-mono overflow-x-auto leading-relaxed whitespace-pre">{{ trim($slot) }}</pre>
</div>
