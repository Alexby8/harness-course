@props(['column', 'sort', 'direction'])
<th scope="col" @if($sort === $column) aria-sort="{{ $direction === 'asc' ? 'ascending' : 'descending' }}" @endif>
    <button type="button" wire:click="sortBy('{{ $column }}')" class="inline-flex cursor-pointer items-center gap-1.5 text-left hover:text-white">
        {{ $slot }}
        <span aria-hidden="true" class="{{ $sort === $column ? 'text-indigo-400' : 'text-gray-600' }}">{{ $sort === $column ? ($direction === 'asc' ? '↑' : '↓') : '↕' }}</span>
    </button>
</th>
