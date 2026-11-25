@php
    $items = [
        ['route' => 'dashboard', 'label' => 'Мои боты', 'active' => request()->routeIs('dashboard', 'bots.*'), 'path' => 'M9 3h6v3H9z M5 9h14v12H5z M9 13h.01 M15 13h.01 M9 17h6 M3 12v5 M21 12v5 M12 6v3'],
        ['route' => 'dashboard.api-key', 'label' => 'API-ключ', 'active' => request()->routeIs('dashboard.api-key'), 'path' => 'M14.5 12.5a5 5 0 1 0-3-3 M11.5 9.5 3 18v3h3v-3h3v-3h3l2.5-2.5 M17 7h.01'],
        ['route' => 'dashboard.api-logs', 'label' => 'API-логи', 'active' => request()->routeIs('dashboard.api-logs'), 'path' => 'M14 2H6v20h12V6z M14 2v4h4 M9 10h6 M9 14h6 M9 18h4'],
        ['route' => 'dashboard.webhook-events', 'label' => 'Webhook-события', 'active' => request()->routeIs('dashboard.webhook-events'), 'path' => 'M7 3v3 M17 3v3 M5 6h14v14H5z M5 10h14 M9 15h6 M12 12l3 3-3 3'],
        ['route' => 'docs', 'label' => 'Документация', 'active' => false, 'path' => 'M12 5v15 M12 5C9 3 5 3 3 4v15c3-1 6-1 9 1 M12 5c3-2 7-2 9-1v15c-3-1-6-1-9 1'],
    ];
@endphp
<nav aria-label="Навигация кабинета" class="space-y-1">
    @foreach($items as $item)
        <a href="{{ route($item['route']) }}"
           class="flex items-center gap-3 rounded-lg px-3 py-2.5 text-sm font-medium transition {{ $item['active'] ? 'bg-indigo-500/10 text-indigo-300' : 'text-gray-400 hover:bg-gray-900 hover:text-gray-100' }}"
           @if($item['active']) aria-current="page" @endif>
            <svg aria-hidden="true" class="h-5 w-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="{{ $item['path'] }}"/></svg>
            {{ $item['label'] }}
        </a>
    @endforeach
</nav>
