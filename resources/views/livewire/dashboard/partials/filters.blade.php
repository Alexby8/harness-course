<div class="mb-5 rounded-xl border border-gray-800 bg-gray-900 p-4 sm:p-5">
    <div class="grid items-end gap-4 sm:grid-cols-2 xl:grid-cols-4">
        <div>
            <label for="journal-bot" class="mb-1.5 block text-xs font-medium text-gray-400">Бот</label>
            <select id="journal-bot" wire:model.live="bot" class="journal-input">
                <option value="">Все мои боты</option>
                @foreach($bots as $filterBot)
                    <option value="{{ $filterBot->public_id }}">{{ $filterBot->name }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label for="journal-status" class="mb-1.5 block text-xs font-medium text-gray-400">Статус</label>
            <select id="journal-status" wire:model.live="status" class="journal-input">
                <option value="">Все статусы</option>
                @foreach($this->statusOptions() as $value => $label)
                    <option value="{{ $value }}">{{ $label }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label for="journal-from" class="mb-1.5 block text-xs font-medium text-gray-400">Дата с</label>
            <input id="journal-from" type="date" wire:model.live="dateFrom" class="journal-input">
        </div>
        <div>
            <label for="journal-to" class="mb-1.5 block text-xs font-medium text-gray-400">Дата по</label>
            <input id="journal-to" type="date" wire:model.live="dateTo" class="journal-input">
        </div>
    </div>
    <div class="mt-4 flex justify-end">
        <button type="button" wire:click="resetFilters" class="cursor-pointer text-xs text-gray-400 transition hover:text-white">Сбросить фильтры</button>
    </div>
    @if($errors->any())
        <ul role="alert" class="mt-3 space-y-1 text-sm text-red-400">
            @foreach($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    @endif
</div>
