<div class="mb-6 space-y-2 text-xs leading-5 text-gray-500">
    <div class="flex flex-wrap items-center gap-x-5 gap-y-1">
        <time datetime="{{ $article['date'] }}" class="inline-flex items-center gap-1.5 whitespace-nowrap">
            <svg class="h-3.5 w-3.5 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" aria-hidden="true">
                <rect x="3" y="5" width="18" height="16" rx="2" />
                <path stroke-linecap="round" d="M16 3v4M8 3v4M3 11h18" />
            </svg>
            {{ $article['display_date'] }}
        </time>
        <span class="inline-flex items-center gap-1.5 whitespace-nowrap">
            <svg class="h-3.5 w-3.5 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" aria-hidden="true">
                <circle cx="12" cy="12" r="9" />
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 7v5l3 2" />
            </svg>
            {{ $article['reading_time'] }} чтения
        </span>
    </div>
    <div class="flex flex-wrap items-center gap-x-2 gap-y-1">
        <span>Тема:</span>
        <span class="text-gray-400">{{ $category ?? $article['category'] }}</span>
        <span class="text-gray-700" aria-hidden="true">/</span>
        <span>{{ $topics ?? $article['topics'] ?? 'Telegram Bot API' }}</span>
    </div>
</div>
