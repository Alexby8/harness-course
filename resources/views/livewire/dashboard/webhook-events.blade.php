<div>
    <x-dashboard.journal title="Webhook-события" description="Доставка событий на адреса ваших ботов. События хранятся 30 дней, события с окончательной ошибкой — 60 дней." :records="$records">
        <x-slot:filters>
            @include('livewire.dashboard.partials.filters')
        </x-slot:filters>
        <x-slot:head>
            <x-dashboard.sort-heading column="created_at" :sort="$sort" :direction="$direction">Дата</x-dashboard.sort-heading>
            <th scope="col">Бот</th>
            <th scope="col">Статус</th>
            <th scope="col">URL</th>
            <th scope="col">Попытки</th>
            <th scope="col">HTTP</th>
            <th scope="col">Ошибка</th>
            <th scope="col">Доставлен</th>
        </x-slot:head>
        @forelse($records as $record)
            <tr wire:key="webhook-event-{{ $record->id }}">
                <td class="whitespace-nowrap text-gray-400">{{ $record->created_at->format('d.m.Y H:i:s') }}</td>
                <td><a href="{{ route('bots.show', $record->bot->public_id) }}" class="text-indigo-300 hover:text-indigo-200">{{ $record->bot->name }}</a></td>
                <td><x-dashboard.status :status="$record->status->value" :label="$record->status->label()" /></td>
                <td class="min-w-64"><x-dashboard.expandable-text :value="$record->target_url" :limit="40" /></td>
                <td class="tabular-nums text-gray-400">{{ $record->attempts }}</td>
                <td class="tabular-nums">{{ $record->last_http_status ?? '—' }}</td>
                <td class="min-w-64"><x-dashboard.expandable-text :value="$record->last_error" :max-length="1000" /></td>
                <td class="whitespace-nowrap text-gray-400">{{ $record->delivered_at?->format('d.m.Y H:i') ?? '—' }}</td>
            </tr>
        @empty
            <tr><td colspan="8" class="py-12! text-center text-gray-500">Webhook-события не найдены. Попробуйте изменить фильтры или подключите вебхук бота.</td></tr>
        @endforelse
    </x-dashboard.journal>
</div>
