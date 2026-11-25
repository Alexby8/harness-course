@props(['title', 'description', 'records'])
<section aria-label="{{ $title }}">
    <div class="mb-6 flex flex-wrap items-start justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold tracking-tight">{{ $title }}</h1>
            <p class="mt-2 max-w-3xl text-sm leading-6 text-gray-400">{{ $description }}</p>
        </div>
        <button type="button" wire:click="$refresh" wire:loading.attr="disabled" class="cursor-pointer rounded-lg border border-gray-700 px-4 py-2 text-sm font-medium text-gray-300 transition hover:border-gray-500 hover:text-white disabled:opacity-50">Обновить</button>
    </div>

    {{ $filters }}

    <div class="overflow-hidden rounded-xl border border-gray-800 bg-gray-900/40">
        <div role="region" aria-label="Таблица: {{ $title }}" tabindex="0" class="overflow-x-auto focus-visible:outline-2 focus-visible:outline-indigo-500" wire:loading.class="opacity-50">
            <table class="journal-table">
                <thead><tr>{{ $head }}</tr></thead>
                <tbody>{{ $slot }}</tbody>
            </table>
        </div>
        <div class="border-t border-gray-800 px-4 py-4 sm:px-5">
            {{ $records->onEachSide(1)->links('livewire.dashboard.pagination') }}
        </div>
    </div>
    <p wire:loading.delay role="status" class="mt-3 text-xs text-gray-500">Загрузка записей…</p>
</section>
