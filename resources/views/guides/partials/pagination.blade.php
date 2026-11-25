@if($paginator->hasPages())
    @php
        $pageUrl = fn (int $page): string => $page === 1 ? $paginator->path() : $paginator->url($page);
        $linkClass = 'inline-flex min-h-11 min-w-11 items-center justify-center rounded-lg border border-gray-800 px-3 text-sm whitespace-nowrap text-gray-300 transition hover:border-indigo-500/50 hover:text-indigo-200 focus-visible:outline-2 focus-visible:outline-offset-4 focus-visible:outline-indigo-400';
    @endphp
    <nav class="mt-8" aria-label="Страницы руководств">
        <p class="mb-4 text-center text-sm text-gray-500">Страница {{ $paginator->currentPage() }} из {{ $paginator->lastPage() }}</p>
        <div class="flex items-center justify-center gap-2">
            @if($paginator->onFirstPage())
                <span class="inline-flex min-h-11 items-center px-3 text-sm whitespace-nowrap text-gray-600" aria-disabled="true">← Назад</span>
            @else
                <a href="{{ $pageUrl($paginator->currentPage() - 1) }}" rel="prev" class="{{ $linkClass }}">← Назад</a>
            @endif

            <div class="hidden items-center gap-2 md:flex">
                @foreach($elements as $element)
                    @if(is_string($element))
                        <span class="px-1 text-gray-500" aria-hidden="true">{{ $element }}</span>
                    @else
                        @foreach($element as $page => $url)
                            @if($page === $paginator->currentPage())
                                <span class="inline-flex min-h-11 min-w-11 items-center justify-center rounded-lg border border-indigo-500/40 bg-indigo-500/10 px-3 text-sm text-indigo-200" aria-current="page">{{ $page }}</span>
                            @else
                                <a href="{{ $pageUrl($page) }}" class="{{ $linkClass }}" aria-label="Страница {{ $page }}">{{ $page }}</a>
                            @endif
                        @endforeach
                    @endif
                @endforeach
            </div>

            @if($paginator->hasMorePages())
                <a href="{{ $pageUrl($paginator->currentPage() + 1) }}" rel="next" class="{{ $linkClass }}">Вперёд →</a>
            @else
                <span class="inline-flex min-h-11 items-center px-3 text-sm whitespace-nowrap text-gray-600" aria-disabled="true">Вперёд →</span>
            @endif
        </div>
    </nav>
@endif
