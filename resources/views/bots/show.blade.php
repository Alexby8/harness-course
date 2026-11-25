@extends('layouts.app')
@section('title', $bot->name . ' — BotGate')

@section('content')
<div class="max-w-2xl wrap-anywhere">
    <div class="flex items-center gap-3 mb-6">
        <a href="{{ route('dashboard') }}" class="text-gray-500 hover:text-gray-300 transition text-sm">← Мои боты</a>
    </div>

    <div class="flex flex-wrap items-center gap-3 mb-4">
        <h1 class="wrap-anywhere text-2xl font-bold">{{ $bot->name }}</h1>
        @if($bot->status->isActive())
        <span class="text-xs bg-green-900/50 text-green-400 px-2 py-0.5 rounded-full">активен</span>
        @else
        <span class="text-xs bg-gray-700 text-gray-400 px-2 py-0.5 rounded-full">отключён</span>
        @endif
    </div>

    <div class="mb-6 flex flex-wrap gap-4 text-sm">
        <a href="{{ route('dashboard.api-logs', ['bot' => $bot->public_id]) }}" class="text-indigo-400 hover:text-indigo-300">API-логи</a>
        <a href="{{ route('dashboard.webhook-events', ['bot' => $bot->public_id]) }}" class="text-indigo-400 hover:text-indigo-300">Webhook-события</a>
    </div>

    {{-- Bot info --}}
    <div class="bg-gray-900 border border-gray-800 rounded-xl p-5 mb-5 space-y-3">
        <div class="grid grid-cols-3 gap-2 text-sm">
            <span class="text-gray-500">Bot Public ID</span>
            <span class="col-span-2 font-mono text-gray-200">{{ $bot->public_id }}</span>
        </div>
        <div class="grid grid-cols-3 gap-2 text-sm">
            <span class="text-gray-500">Telegram username</span>
            <span class="col-span-2 text-gray-200">{{ $bot->telegram_username ? '@'.$bot->telegram_username : '—' }}</span>
        </div>
        <div class="grid grid-cols-3 gap-2 text-sm">
            <span class="text-gray-500">Имя бота</span>
            <span class="col-span-2 text-gray-200">{{ $bot->telegram_first_name ?? '—' }}</span>
        </div>
        <div class="grid grid-cols-3 gap-2 text-sm">
            <span class="text-gray-500">Токен</span>
            <span class="col-span-2 font-mono text-gray-400">{{ $bot->masked_token }}</span>
        </div>
        <div class="grid grid-cols-3 gap-2 text-sm">
            <span class="text-gray-500">Webhook Secret</span>
            <span class="col-span-2 font-mono text-gray-400 break-all">{{ $bot->webhook_secret }}</span>
        </div>
        <div class="grid grid-cols-3 gap-2 text-sm">
            <span class="text-gray-500">Добавлен</span>
            <span class="col-span-2 text-gray-400">{{ $bot->created_at->format('d.m.Y, H:i') }}</span>
        </div>
    </div>

    {{-- Webhook section --}}
    <div class="bg-gray-900 border border-gray-800 rounded-xl p-5 mb-5">
        <div class="flex items-center justify-between mb-1">
            <h3 class="text-sm font-medium text-gray-300">Пересылка вебхуков <span class="text-gray-600 font-normal">(необязательно)</span></h3>
            @if($webhookStatus === 'active')
            <span class="text-xs bg-green-900/50 text-green-400 px-2 py-0.5 rounded-full">активна</span>
            @endif
        </div>
        <p class="text-xs text-gray-500 mb-4 leading-relaxed">
            BotGate может принять обновления от Telegram и переслать их на ваш сервер.
            Это удобно, если прямой вебхук нестабилен или недоступен из России.
        </p>

        @if($webhookStatus === 'unavailable')
        {{-- Telegram недоступен --}}
        <p class="text-xs text-gray-500">Не удалось получить данные от Telegram — статус вебхука неизвестен.</p>

        @elseif($webhookStatus === 'none')
        {{-- Ничего не настроено --}}
        <form method="POST" action="{{ route('bots.activate-webhook', $bot->public_id) }}" class="flex gap-2 items-start">
            @csrf
            <div class="min-w-0 flex-1">
                <input type="url" name="webhook_url" value="{{ old('webhook_url') }}" required
                    placeholder="https://ваш-сервер.ru/webhook"
                    class="w-full bg-gray-800 border border-gray-700 rounded-lg px-3 py-1.5 text-sm text-white focus:outline-none focus:border-indigo-500 @error('webhook_url') border-red-500 @enderror">
                @error('webhook_url')
                <p class="mt-1 text-xs text-red-400">{{ $message }}</p>
                @enderror
            </div>
            <button class="cursor-pointer shrink-0 bg-indigo-600 hover:bg-indigo-500 text-white text-xs font-medium px-3 py-1.5 rounded-lg transition">
                Подключить
            </button>
        </form>

        @elseif($webhookStatus === 'active')
        {{-- Всё работает --}}
        <div class="space-y-2 mb-4 text-sm">
            <div class="flex gap-3">
                <span class="text-gray-500 shrink-0">Доставка на</span>
                <span class="text-gray-200 break-all min-w-0">{{ $bot->webhook_url }}</span>
            </div>
            @if(($telegramWebhook['pending_update_count'] ?? 0) > 0)
            <div class="flex gap-3">
                <span class="text-gray-500 shrink-0">В очереди</span>
                <span class="text-gray-300">{{ $telegramWebhook['pending_update_count'] }}</span>
            </div>
            @endif
            @if(!empty($telegramWebhook['last_error_message']))
            <div class="flex gap-3">
                <span class="text-gray-500 shrink-0">Ошибка Telegram</span>
                <span class="text-red-400 text-xs min-w-0 break-all">{{ $telegramWebhook['last_error_message'] }}</span>
            </div>
            @endif
        </div>
        <div class="flex gap-2 flex-wrap items-center">
            <p class="text-xs text-gray-600">Изменить URL:</p>
            <form method="POST" action="{{ route('bots.activate-webhook', $bot->public_id) }}" class="flex min-w-0 flex-1 gap-2 items-center">
                @csrf
                <input type="url" name="webhook_url" value="{{ old('webhook_url', $bot->webhook_url) }}" required
                    class="flex-1 min-w-0 bg-gray-800 border border-gray-700 rounded-lg px-3 py-1.5 text-xs text-white focus:outline-none focus:border-indigo-500">
                <button class="cursor-pointer shrink-0 text-xs text-indigo-400 hover:text-indigo-300 border border-indigo-800 hover:border-indigo-600 px-3 py-1.5 rounded-lg transition">
                    Сохранить
                </button>
            </form>
            <form method="POST" action="{{ route('bots.deactivate-webhook', $bot->public_id) }}"
                  onsubmit="return confirm('Отключить вебхук? Telegram перестанет отправлять обновления через BotGate.')">
                @csrf
                <button class="cursor-pointer text-xs text-gray-500 hover:text-gray-300 transition">
                    Отключить
                </button>
            </form>
        </div>

        @elseif($webhookStatus === 'stale_registered')
        {{-- Наш вебхук в Telegram, но URL доставки не задан --}}
        <p class="text-xs text-yellow-600 mb-3">BotGate получает обновления от Telegram, но не знает куда их пересылать. Укажите URL вашего сервера:</p>
        <form method="POST" action="{{ route('bots.activate-webhook', $bot->public_id) }}" class="flex gap-2 items-start mb-2">
            @csrf
            <div class="min-w-0 flex-1">
                <input type="url" name="webhook_url" value="{{ old('webhook_url') }}" required
                    placeholder="https://ваш-сервер.ru/webhook"
                    class="w-full bg-gray-800 border border-gray-700 rounded-lg px-3 py-1.5 text-sm text-white focus:outline-none focus:border-indigo-500">
            </div>
            <button class="cursor-pointer shrink-0 bg-indigo-600 hover:bg-indigo-500 text-white text-xs font-medium px-3 py-1.5 rounded-lg transition">
                Сохранить
            </button>
        </form>
        <form method="POST" action="{{ route('bots.deactivate-webhook', $bot->public_id) }}">
            @csrf
            <button class="cursor-pointer text-xs text-gray-500 hover:text-gray-300 transition">
                Снять вебхук из Telegram
            </button>
        </form>

        @elseif($webhookStatus === 'not_registered')
        {{-- URL задан, но Telegram не подключён --}}
        <p class="text-xs text-yellow-600 mb-3">URL доставки задан, но BotGate не зарегистрирован в Telegram — обновления не поступают.</p>
        <p class="text-xs text-gray-500 mb-3">Доставка на: <span class="text-gray-300">{{ $bot->webhook_url }}</span></p>
        <div class="flex gap-3 flex-wrap">
            <form method="POST" action="{{ route('bots.resync-webhook', $bot->public_id) }}">
                @csrf
                <button class="cursor-pointer text-xs text-indigo-400 hover:text-indigo-300 border border-indigo-800 hover:border-indigo-600 px-3 py-1.5 rounded-lg transition">
                    Зарегистрировать в Telegram
                </button>
            </form>
            <form method="POST" action="{{ route('bots.deactivate-webhook', $bot->public_id) }}">
                @csrf
                <button class="cursor-pointer text-xs text-gray-500 hover:text-gray-300 transition">
                    Отменить
                </button>
            </form>
        </div>

        @elseif($webhookStatus === 'foreign')
        {{-- Чужой вебхук — нейтральный тон --}}
        <p class="text-xs text-gray-400 mb-1">В Telegram зарегистрирован вебхук на другой адрес:</p>
        <p class="text-xs font-mono text-gray-300 break-all mb-3">{{ $telegramWebhook['url'] }}</p>
        <p class="text-xs text-gray-500 mb-3">Если вы обрабатываете обновления самостоятельно — всё в порядке, ничего менять не нужно. Если хотите подключить пересылку через BotGate, сначала снимите текущий вебхук:</p>
        <form method="POST" action="{{ route('bots.deactivate-webhook', $bot->public_id) }}">
            @csrf
            <button class="cursor-pointer text-xs text-gray-400 hover:text-white border border-gray-700 hover:border-gray-500 px-3 py-1.5 rounded-lg transition">
                Снять вебхук
            </button>
        </form>

        @elseif($webhookStatus === 'conflict')
        {{-- Конфликт: чужой в Telegram + наш URL задан --}}
        <p class="text-xs text-yellow-600 mb-2">В Telegram зарегистрирован другой вебхук — BotGate не получает обновления.</p>
        <div class="text-xs text-gray-500 space-y-1 mb-3">
            <p>В Telegram: <span class="font-mono text-gray-300 break-all">{{ $telegramWebhook['url'] }}</span></p>
            <p>Доставка на: <span class="text-gray-300">{{ $bot->webhook_url }}</span></p>
        </div>
        <p class="text-xs text-gray-500 mb-3">Пересинхронизация перезапишет вебхук в Telegram на адрес BotGate.</p>
        <div class="flex gap-2 flex-wrap">
            <form method="POST" action="{{ route('bots.resync-webhook', $bot->public_id) }}">
                @csrf
                <button class="cursor-pointer text-xs text-indigo-400 hover:text-indigo-300 border border-indigo-800 hover:border-indigo-600 px-3 py-1.5 rounded-lg transition">
                    Пересинхронизировать
                </button>
            </form>
            <form method="POST" action="{{ route('bots.deactivate-webhook', $bot->public_id) }}">
                @csrf
                <button class="cursor-pointer text-xs text-gray-500 hover:text-gray-300 transition">
                    Отключить
                </button>
            </form>
        </div>
        @endif
    </div>

    {{-- Actions --}}
    <div class="flex flex-wrap gap-3">
        <a href="{{ route('bots.edit', $bot->public_id) }}"
           class="bg-gray-700 hover:bg-gray-600 text-sm text-gray-200 px-4 py-2 rounded-lg transition">
            Редактировать
        </a>
        @if($bot->status->isActive())
        <form method="POST" action="{{ route('bots.disable', $bot->public_id) }}">
            @csrf
            <button class="cursor-pointer bg-gray-700 hover:bg-gray-600 text-sm text-gray-200 px-4 py-2 rounded-lg transition">
                Отключить бота
            </button>
        </form>
        @else
        <form method="POST" action="{{ route('bots.enable', $bot->public_id) }}">
            @csrf
            <button class="cursor-pointer bg-indigo-600 hover:bg-indigo-500 text-sm text-white px-4 py-2 rounded-lg transition">
                Включить бота
            </button>
        </form>
        @endif

        <form method="POST" action="{{ route('bots.destroy', $bot->public_id) }}"
              onsubmit="return confirm('Удалить этого бота? Это действие необратимо.')">
            @csrf
            @method('DELETE')
            <button class="cursor-pointer bg-red-900/40 hover:bg-red-900/70 text-sm text-red-400 px-4 py-2 rounded-lg border border-red-800 transition">
                Удалить бота
            </button>
        </form>
    </div>
</div>
@endsection
