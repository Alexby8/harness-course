<div>
    <x-dashboard.journal title="API-логи" description="Запросы к API ваших ботов. Срок хранения логов — 14 дней." :records="$records">
        <x-slot:filters>
            @include('livewire.dashboard.partials.filters')
        </x-slot:filters>
        <x-slot:head>
            <x-dashboard.sort-heading column="created_at" :sort="$sort" :direction="$direction">Дата</x-dashboard.sort-heading>
            <th scope="col">Бот</th>
            <th scope="col">Метод</th>
            <th scope="col">Тип</th>
            <th scope="col">HTTP</th>
            <th scope="col">OK</th>
            <x-dashboard.sort-heading column="duration_ms" :sort="$sort" :direction="$direction">мс</x-dashboard.sort-heading>
            <th scope="col">Ошибка</th>
        </x-slot:head>
        @forelse($records as $record)
            <tr wire:key="api-log-{{ $record->id }}">
                <td class="whitespace-nowrap text-gray-400">{{ $record->created_at->format('d.m.Y H:i:s') }}</td>
                <td><a href="{{ route('bots.show', $record->bot->public_id) }}" class="text-indigo-300 hover:text-indigo-200">{{ $record->bot->name }}</a></td>
                <td class="font-mono text-xs">{{ $record->method }}</td>
                <td class="text-gray-400">{{ $record->content_type }}</td>
                <td class="tabular-nums">{{ $record->telegram_http_status ?? '—' }}</td>
                <td>
                    @if($record->telegram_ok === null)
                        <x-dashboard.status status="no_response" label="Нет ответа" />
                    @elseif($record->telegram_ok)
                        <x-dashboard.status status="success" label="Да" />
                    @else
                        <x-dashboard.status status="error" label="Нет" />
                    @endif
                </td>
                <td class="tabular-nums text-gray-400">{{ $record->duration_ms ?? '—' }}</td>
                <td class="min-w-64"><x-dashboard.expandable-text :value="$record->error" /></td>
            </tr>
        @empty
            <tr><td colspan="8" class="py-12! text-center text-gray-500">API-логи не найдены. Попробуйте изменить фильтры или выполните запрос к API.</td></tr>
        @endforelse
    </x-dashboard.journal>
</div>
