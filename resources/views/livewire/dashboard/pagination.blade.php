<div class="flex flex-wrap items-center justify-between gap-4">
    <p class="text-xs text-gray-500">
        @if($paginator->total() > 0)
            Показано {{ $paginator->firstItem() }}–{{ $paginator->lastItem() }} из {{ $paginator->total() }}
        @else
            Всего записей: 0
        @endif
    </p>
    @if($paginator->hasPages())
        <nav aria-label="Пагинация" class="flex flex-wrap items-center gap-1">
            <button type="button" wire:click="previousPage('{{ $paginator->getPageName() }}')" wire:loading.attr="disabled" @disabled($paginator->onFirstPage()) class="cursor-pointer rounded-lg border border-gray-700 px-3 py-2 text-xs text-gray-300 hover:bg-gray-800 disabled:cursor-default disabled:opacity-40">← Назад</button>
            @foreach($elements as $element)
                @if(is_string($element))
                    <span class="hidden px-2 text-xs text-gray-600 sm:inline">{{ $element }}</span>
                @else
                    @foreach($element as $page => $url)
                        @if($page === $paginator->currentPage())
                            <span aria-current="page" aria-label="Страница {{ $page }}" class="rounded-lg bg-indigo-500/15 px-3 py-2 text-xs font-medium text-indigo-300">{{ $page }}</span>
                        @else
                            <button type="button" wire:key="page-{{ $paginator->getPageName() }}-{{ $page }}" wire:click="gotoPage({{ $page }}, '{{ $paginator->getPageName() }}')" wire:loading.attr="disabled" aria-label="Перейти на страницу {{ $page }}" class="hidden cursor-pointer rounded-lg px-3 py-2 text-xs text-gray-400 hover:bg-gray-800 hover:text-white disabled:opacity-40 sm:inline-flex">{{ $page }}</button>
                        @endif
                    @endforeach
                @endif
            @endforeach
            <button type="button" wire:click="nextPage('{{ $paginator->getPageName() }}')" wire:loading.attr="disabled" @disabled(! $paginator->hasMorePages()) class="cursor-pointer rounded-lg border border-gray-700 px-3 py-2 text-xs text-gray-300 hover:bg-gray-800 disabled:cursor-default disabled:opacity-40">Вперёд →</button>
        </nav>
    @endif
</div>
