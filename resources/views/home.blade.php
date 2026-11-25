<!DOCTYPE html>
<html lang="ru" class="scroll-smooth bg-gray-950">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>BotGate — прокси для Telegram Bot API из России без сбоев</title>
    <meta name="description" content="BotGate — надёжный шлюз для Telegram Bot API. Отправляйте запросы к Telegram из России без прокси и сбоев: единый API-ключ, шифрование токенов, webhook с повторными попытками. Подключение за несколько минут.">
    <meta name="keywords" content="Telegram Bot API, прокси Telegram, telegram bot api россия, telegram api без прокси, telegram bot webhook, шлюз telegram">
    <link rel="canonical" href="{{ config('app.url') }}">
    <meta name="robots" content="index, follow">
    <meta name="theme-color" content="#030712">

    @include('partials.favicon')


    {{-- Open Graph --}}
    <meta property="og:type" content="website">
    <meta property="og:site_name" content="BotGate">
    <meta property="og:locale" content="ru_RU">
    <meta property="og:title" content="BotGate — прокси для Telegram Bot API из России без сбоев">
    <meta property="og:description" content="Надёжный шлюз для Telegram Bot API: единый API-ключ, шифрование токенов, webhook с повторными попытками. Подключение за несколько минут.">
    <meta property="og:url" content="{{ config('app.url') }}">
    <meta property="og:image" content="{{ asset('images/og-image.png') }}">
    <meta property="og:image:width" content="1536">
    <meta property="og:image:height" content="1024">

    {{-- Twitter --}}
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="BotGate — прокси для Telegram Bot API из России без сбоев">
    <meta name="twitter:description" content="Надёжный шлюз для Telegram Bot API: единый API-ключ, шифрование токенов, webhook с повторными попытками.">
    <meta name="twitter:image" content="{{ asset('images/og-image.png') }}">

    <script type="application/ld+json">{!! $jsonLd !!}</script>

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-full bg-gray-950 text-gray-100 font-sans antialiased selection:bg-indigo-500/30">

{{-- ===== Навигация ===== --}}
@include('partials.site-navigation', ['showHomeSections' => true])

{{-- ===== Hero ===== --}}
<header class="relative overflow-hidden">
    {{-- фоновое свечение --}}
    <div aria-hidden="true" class="pointer-events-none absolute inset-0 -z-10">
        <div class="absolute -top-40 left-1/2 -translate-x-1/2 h-[420px] w-[720px] max-w-full rounded-full bg-indigo-600/20 blur-[120px]"></div>
        <div class="absolute top-32 right-0 h-72 w-72 rounded-full bg-fuchsia-600/10 blur-[110px]"></div>
    </div>

    <div class="max-w-6xl mx-auto px-4 sm:px-6 pt-16 sm:pt-24 pb-16 grid lg:grid-cols-2 gap-12 lg:gap-10 items-center">
        <div class="space-y-7 min-w-0">
            <span class="inline-flex items-center gap-2 rounded-full border border-indigo-500/30 bg-indigo-500/10 px-3 py-1 text-xs font-medium text-indigo-300">
                <span class="h-1.5 w-1.5 rounded-full bg-indigo-400"></span>
                Шлюз для Telegram Bot API
            </span>

            <h1 class="text-3xl sm:text-5xl lg:text-[3.25rem] font-bold leading-[1.15] sm:leading-[1.1] tracking-tight text-white text-balance">
                Telegram Bot API,<br class="hidden sm:block"> который работает
                <span class="text-indigo-400">из России</span>
            </h1>

            <p class="text-lg text-gray-400 max-w-xl leading-relaxed">
                BotGate — это прослойка между вашим приложением и Telegram. Запросы уходят через зарубежный
                сервер, поэтому бот отвечает стабильно — без личных прокси и переключений VPN.
            </p>

            <div class="flex flex-col sm:flex-row gap-3">
                <a href="{{ route('register') }}"
                   class="inline-flex items-center justify-center gap-2 bg-indigo-600 hover:bg-indigo-500 text-white font-medium px-6 py-3 rounded-xl transition shadow-lg shadow-indigo-600/25">
                    Начать бесплатно
                    <svg class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M3 10a.75.75 0 0 1 .75-.75h9.69L9.97 5.78a.75.75 0 1 1 1.06-1.06l5 5a.75.75 0 0 1 0 1.06l-5 5a.75.75 0 1 1-1.06-1.06l3.47-3.47H3.75A.75.75 0 0 1 3 10Z" clip-rule="evenodd"/></svg>
                </a>
                <a href="{{ route('docs') }}"
                   class="inline-flex items-center justify-center gap-2 border border-gray-700 hover:border-gray-500 bg-gray-900/40 text-gray-200 font-medium px-6 py-3 rounded-xl transition">
                    Документация
                </a>
            </div>

            <p class="text-sm text-gray-500">Регистрация по email · подключение за несколько минут</p>
        </div>

        {{-- Терминал-карточка с примером запроса --}}
        <div class="relative min-w-0">
            <div class="rounded-2xl border border-gray-800 bg-gray-900/70 shadow-2xl shadow-black/40 overflow-hidden">
                <div class="flex items-center gap-2 px-4 py-3 border-b border-gray-800 bg-gray-900">
                    <span class="h-3 w-3 rounded-full bg-red-500/70"></span>
                    <span class="h-3 w-3 rounded-full bg-yellow-500/70"></span>
                    <span class="h-3 w-3 rounded-full bg-green-500/70"></span>
                    <span class="ml-2 text-xs text-gray-500 font-mono">sendMessage</span>
                </div>
                <pre class="px-4 sm:px-5 py-4 sm:py-5 text-xs sm:text-[13px] leading-relaxed font-mono text-gray-300 overflow-x-auto whitespace-pre"><span class="text-fuchsia-400">POST</span> {{ $baseUrl }}/v1/bots/<span class="text-indigo-300">bot_8Kx91aPqZ2</span>/sendMessage
<span class="text-gray-500">Authorization:</span> Bearer <span class="text-emerald-300">bg_live_••••X7kP</span>
<span class="text-gray-500">Content-Type:</span> application/json

{
  <span class="text-sky-300">"chat_id"</span>: <span class="text-amber-300">123456789</span>,
  <span class="text-sky-300">"text"</span>: <span class="text-emerald-300">"Привет от BotGate!"</span>
}</pre>
            </div>
        </div>
    </div>
</header>

{{-- ===== Проблема и решение ===== --}}
<section class="max-w-6xl mx-auto px-4 sm:px-6 py-16 sm:py-20">
    <div class="grid lg:grid-cols-2 gap-6">
        <div class="rounded-2xl border border-gray-800 bg-gray-900/40 p-7">
            <div class="text-xs font-semibold uppercase tracking-wider text-red-400/80 mb-3">Проблема</div>
            <h2 class="text-xl font-semibold text-white mb-3">Telegram Bot API нестабилен из России</h2>
            <p class="text-gray-400 leading-relaxed text-sm">
                Прямые запросы к <code class="text-pink-400">api.telegram.org</code> часто обрываются. Приходится
                поднимать и оплачивать прокси, следить за их доступностью и ловить плавающие ошибки соединения.
            </p>
        </div>
        <div class="rounded-2xl border border-indigo-500/30 bg-indigo-500/5 p-7">
            <div class="text-xs font-semibold uppercase tracking-wider text-indigo-300 mb-3">Решение</div>
            <h2 class="text-xl font-semibold text-white mb-3">Один стабильный шлюз</h2>
            <p class="text-gray-400 leading-relaxed text-sm">
                Вы обращаетесь к BotGate, а он выполняет запрос к Telegram с зарубежного сервера и возвращает
                ответ как есть. Меняется только адрес — формат запросов остаётся привычным.
            </p>
        </div>
    </div>

    {{-- схема потока --}}
    <div class="mt-6 rounded-2xl border border-gray-800 bg-gray-900/40 p-6 sm:p-10">
        <div class="flex flex-col sm:flex-row items-center justify-center gap-3 sm:gap-2">
            <div class="flex-1 w-full sm:w-auto rounded-xl border border-gray-800 bg-gray-950/60 px-5 py-4 text-center">
                <div class="text-sm font-medium text-white">Ваше приложение</div>
                <div class="text-xs text-gray-500 mt-1">отправляет запрос</div>
            </div>
            <svg class="h-5 w-5 text-gray-600 rotate-90 sm:rotate-0 shrink-0" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M3 10a.75.75 0 0 1 .75-.75h9.69L9.97 5.78a.75.75 0 1 1 1.06-1.06l5 5a.75.75 0 0 1 0 1.06l-5 5a.75.75 0 1 1-1.06-1.06l3.47-3.47H3.75A.75.75 0 0 1 3 10Z" clip-rule="evenodd"/></svg>
            <div class="flex-1 w-full sm:w-auto rounded-xl border border-indigo-500/40 bg-indigo-500/10 px-5 py-4 text-center shadow-lg shadow-indigo-600/10">
                <div class="text-sm font-semibold text-indigo-200">BotGate</div>
                <div class="text-xs text-indigo-300/70 mt-1">зарубежный сервер</div>
            </div>
            <svg class="h-5 w-5 text-gray-600 rotate-90 sm:rotate-0 shrink-0" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M3 10a.75.75 0 0 1 .75-.75h9.69L9.97 5.78a.75.75 0 1 1 1.06-1.06l5 5a.75.75 0 0 1 0 1.06l-5 5a.75.75 0 1 1-1.06-1.06l3.47-3.47H3.75A.75.75 0 0 1 3 10Z" clip-rule="evenodd"/></svg>
            <div class="flex-1 w-full sm:w-auto rounded-xl border border-gray-800 bg-gray-950/60 px-5 py-4 text-center">
                <div class="text-sm font-medium text-white">Telegram Bot API</div>
                <div class="text-xs text-gray-500 mt-1">возвращает ответ</div>
            </div>
        </div>
        <div class="mt-6 rounded-xl border border-emerald-500/20 bg-emerald-500/5 px-5 py-4 text-center text-sm leading-relaxed text-gray-300">
            Постоянные данные, настройки и зашифрованные токены хранятся в России.
            Зарубежные узлы используются только для передачи запросов в Telegram и не хранят пользовательские данные.
        </div>
    </div>
</section>

{{-- ===== Как это работает ===== --}}
<section id="how" class="max-w-6xl mx-auto px-4 sm:px-6 py-16 sm:py-20">
    <div class="text-center max-w-2xl mx-auto mb-12">
        <h2 class="text-3xl sm:text-4xl font-bold text-white tracking-tight">Как подключить бота</h2>
        <p class="text-gray-400 mt-3">Три шага — и запросы к Telegram идут через стабильный шлюз.</p>
    </div>

    <div class="grid sm:grid-cols-3 gap-6">
        <div class="rounded-2xl border border-gray-800 bg-gray-900/40 p-6">
            <div class="h-9 w-9 rounded-lg bg-indigo-500/15 text-indigo-300 font-semibold flex items-center justify-center mb-4">1</div>
            <h3 class="text-white font-medium mb-2">Регистрация</h3>
            <p class="text-sm text-gray-400 leading-relaxed">Создайте аккаунт по email и получите персональный API-ключ.</p>
        </div>
        <div class="rounded-2xl border border-gray-800 bg-gray-900/40 p-6">
            <div class="h-9 w-9 rounded-lg bg-indigo-500/15 text-indigo-300 font-semibold flex items-center justify-center mb-4">2</div>
            <h3 class="text-white font-medium mb-2">Добавьте бота</h3>
            <p class="text-sm text-gray-400 leading-relaxed">Вставьте токен от <a href="https://t.me/BotFather" target="_blank" rel="noopener" class="text-indigo-400 hover:underline">@@BotFather</a> — BotGate проверит его и выдаст Bot ID.</p>
        </div>
        <div class="rounded-2xl border border-gray-800 bg-gray-900/40 p-6">
            <div class="h-9 w-9 rounded-lg bg-indigo-500/15 text-indigo-300 font-semibold flex items-center justify-center mb-4">3</div>
            <h3 class="text-white font-medium mb-2">Отправляйте запросы</h3>
            <p class="text-sm text-gray-400 leading-relaxed">Используйте адрес BotGate вместо <code class="text-pink-400">api.telegram.org</code> — остальное без изменений.</p>
        </div>
    </div>
</section>

{{-- ===== Возможности ===== --}}
<section id="features" class="max-w-6xl mx-auto px-4 sm:px-6 py-16 sm:py-20">
    <div class="text-center max-w-2xl mx-auto mb-12">
        <h2 class="text-3xl sm:text-4xl font-bold text-white tracking-tight">Возможности</h2>
        <p class="text-gray-400 mt-3">Всё необходимое, чтобы запустить бота в продакшене.</p>
    </div>

    <div class="grid sm:grid-cols-2 lg:grid-cols-3 gap-5">
        @php
            $features = [
                ['Стабильная доставка', 'Запросы к Telegram выполняются с зарубежного сервера — без личных прокси и сбоев соединения.', 'M13 10V3L4 14h7v7l9-11h-7z'],
                ['Шифрование токенов', 'Telegram Bot Token хранится зашифрованным и расшифровывается только на время запроса.', 'M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z'],
                ['Полная совместимость', 'Любой метод Bot API проксируется как есть — sendMessage, sendPhoto, editMessageText и другие.', 'M8 9l3 3-3 3m5 0h3M5 20h14a2 2 0 002-2V6a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z'],
                ['Webhook с повторами', 'Приём апдейтов от Telegram и доставка на ваш сервер с автоматическими повторными попытками.', 'M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15'],
                ['Единый API-ключ', 'Один ключ для всех ваших ботов. Перевыпуск в один клик при компрометации.', 'M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z'],
                ['Логи и статистика', 'Количество запросов, ошибок и доставок webhook видны прямо в личном кабинете.', 'M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z'],
            ];
        @endphp

        @foreach($features as [$title, $text, $icon])
            <div class="rounded-2xl border border-gray-800 bg-gray-900/40 p-6 hover:border-gray-700 transition">
                <div class="h-10 w-10 rounded-lg bg-indigo-500/10 text-indigo-400 flex items-center justify-center mb-4">
                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                        <path stroke-linecap="round" stroke-linejoin="round" d="{{ $icon }}"/>
                    </svg>
                </div>
                <h3 class="text-white font-medium mb-2">{{ $title }}</h3>
                <p class="text-sm text-gray-400 leading-relaxed">{{ $text }}</p>
            </div>
        @endforeach
    </div>
</section>

{{-- ===== Официальные SDK ===== --}}
<section id="sdk" class="max-w-6xl mx-auto px-4 sm:px-6 py-16 sm:py-20">
    <div class="text-center max-w-2xl mx-auto mb-12">
        <h2 class="text-3xl sm:text-4xl font-bold text-white tracking-tight">Готовые SDK</h2>
        <p class="text-gray-400 mt-3">Не пишите HTTP-запросы вручную — установите пакет через Composer и работайте с типизированным клиентом.</p>
    </div>

    <div class="grid sm:grid-cols-2 gap-5">
        {{-- PHP SDK --}}
        <div class="flex flex-col rounded-2xl border border-gray-800 bg-gray-900/40 p-6 hover:border-gray-700 transition">
            <div class="flex items-center gap-3 mb-4">
                <span class="inline-flex h-10 w-10 items-center justify-center rounded-lg bg-indigo-500/10 text-indigo-400 font-semibold text-sm">PHP</span>
            </div>
            <p class="text-sm text-gray-400 leading-relaxed mb-4">
                Не зависит от фреймворка: типизированный клиент Bot API, проверка подписи вебхуков и разбор обновлений.
            </p>
            <div class="rounded-lg border border-gray-800 bg-gray-950/60 px-4 py-2.5 font-mono text-xs sm:text-[13px] text-gray-300 overflow-x-auto mb-4">composer require botgate/sdk</div>
            <a href="https://github.com/Alexby8/botgate-php-sdk" target="_blank" rel="noopener"
               class="mt-auto inline-flex items-center gap-2 text-sm text-indigo-400 hover:text-indigo-300 transition">
                <svg class="h-4 w-4" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path fill-rule="evenodd" clip-rule="evenodd" d="M12 .5C5.37.5 0 5.78 0 12.292c0 5.211 3.438 9.63 8.205 11.188.6.111.82-.254.82-.567 0-.28-.01-1.022-.015-2.005-3.338.711-4.042-1.582-4.042-1.582-.546-1.361-1.335-1.725-1.335-1.725-1.087-.731.084-.716.084-.716 1.205.082 1.838 1.215 1.838 1.215 1.07 1.803 2.809 1.282 3.495.981.108-.763.417-1.282.76-1.577-2.665-.295-5.466-1.309-5.466-5.827 0-1.287.465-2.339 1.235-3.164-.135-.298-.54-1.497.105-3.121 0 0 1.005-.316 3.3 1.209a11.5 11.5 0 0 1 3-.398c1.02.006 2.04.136 3 .398 2.28-1.525 3.285-1.209 3.285-1.209.645 1.624.24 2.823.12 3.121.765.825 1.23 1.877 1.23 3.164 0 4.53-2.805 5.527-5.475 5.817.42.354.81 1.077.81 2.182 0 1.578-.015 2.846-.015 3.229 0 .309.21.678.825.561C20.565 21.917 24 17.495 24 12.292 24 5.78 18.627.5 12 .5z"/></svg>
                Открыть на GitHub
            </a>
        </div>

        {{-- Laravel --}}
        <div class="flex flex-col rounded-2xl border border-gray-800 bg-gray-900/40 p-6 hover:border-gray-700 transition">
            <div class="flex items-center gap-3 mb-4">
                <span class="inline-flex h-10 w-10 items-center justify-center rounded-lg bg-red-500/10 text-red-400 font-semibold text-xs">Laravel</span>
            </div>
            <p class="text-sm text-gray-400 leading-relaxed mb-4">
                Сервис-провайдер, фасад, готовый маршрут вебхука с проверкой подписи и событие для входящих обновлений.
            </p>
            <div class="rounded-lg border border-gray-800 bg-gray-950/60 px-4 py-2.5 font-mono text-xs sm:text-[13px] text-gray-300 overflow-x-auto mb-4">composer require botgate/laravel</div>
            <a href="https://github.com/Alexby8/botgate-laravel" target="_blank" rel="noopener"
               class="mt-auto inline-flex items-center gap-2 text-sm text-indigo-400 hover:text-indigo-300 transition">
                <svg class="h-4 w-4" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path fill-rule="evenodd" clip-rule="evenodd" d="M12 .5C5.37.5 0 5.78 0 12.292c0 5.211 3.438 9.63 8.205 11.188.6.111.82-.254.82-.567 0-.28-.01-1.022-.015-2.005-3.338.711-4.042-1.582-4.042-1.582-.546-1.361-1.335-1.725-1.335-1.725-1.087-.731.084-.716.084-.716 1.205.082 1.838 1.215 1.838 1.215 1.07 1.803 2.809 1.282 3.495.981.108-.763.417-1.282.76-1.577-2.665-.295-5.466-1.309-5.466-5.827 0-1.287.465-2.339 1.235-3.164-.135-.298-.54-1.497.105-3.121 0 0 1.005-.316 3.3 1.209a11.5 11.5 0 0 1 3-.398c1.02.006 2.04.136 3 .398 2.28-1.525 3.285-1.209 3.285-1.209.645 1.624.24 2.823.12 3.121.765.825 1.23 1.877 1.23 3.164 0 4.53-2.805 5.527-5.475 5.817.42.354.81 1.077.81 2.182 0 1.578-.015 2.846-.015 3.229 0 .309.21.678.825.561C20.565 21.917 24 17.495 24 12.292 24 5.78 18.627.5 12 .5z"/></svg>
                Открыть на GitHub
            </a>
        </div>
    </div>
</section>

{{-- ===== Финальный CTA ===== --}}
<section class="max-w-6xl mx-auto px-4 sm:px-6 pb-20">
    <div class="relative overflow-hidden rounded-3xl border border-indigo-500/30 bg-gradient-to-br from-indigo-600/20 via-gray-900 to-gray-900 px-6 sm:px-12 py-14 text-center">
        <div aria-hidden="true" class="pointer-events-none absolute -top-24 left-1/2 -translate-x-1/2 h-64 w-[600px] max-w-full rounded-full bg-indigo-600/20 blur-[100px]"></div>
        <h2 class="relative text-3xl sm:text-4xl font-bold text-white tracking-tight">Подключите бота за пару минут</h2>
        <p class="relative text-gray-300 mt-4 max-w-xl mx-auto">Зарегистрируйтесь, добавьте токен и отправьте первый запрос через стабильный шлюз.</p>
        <div class="relative flex flex-col sm:flex-row gap-3 justify-center mt-8">
            <a href="{{ route('register') }}" class="inline-flex items-center justify-center bg-indigo-600 hover:bg-indigo-500 text-white font-medium px-7 py-3 rounded-xl transition shadow-lg shadow-indigo-600/25">Начать бесплатно</a>
            <a href="{{ route('docs') }}" class="inline-flex items-center justify-center border border-gray-700 hover:border-gray-500 text-gray-200 font-medium px-7 py-3 rounded-xl transition">Читать документацию</a>
        </div>
    </div>
</section>

{{-- ===== Footer ===== --}}
<footer class="border-t border-gray-800/80">
    <div class="max-w-6xl mx-auto px-4 sm:px-6 py-10 flex flex-col sm:flex-row items-center justify-between gap-6">
        <a href="/" class="flex items-center gap-2">
            <img src="{{ asset('images/logo-mark.webp') }}" alt="BotGate" width="28" height="28" class="h-7 w-7">
            <span class="text-white font-bold tracking-tight">Bot<span class="text-indigo-400">Gate</span></span>
        </a>
        <div class="flex flex-wrap items-center justify-center gap-x-6 gap-y-2 text-sm text-gray-400">
            <a href="/documents/BotGate_User_Agreement.pdf" target="_blank" class="hover:text-white transition">Пользовательское соглашение</a>
            <a href="/documents/BotGate_Privacy_Policy.pdf" target="_blank" class="hover:text-white transition">Политика конфиденциальности</a>
            <a href="/documents/BotGate_Consent_Personal_Data.pdf" target="_blank" class="hover:text-white transition">Согласие на обработку данных</a>
            <a href="mailto:support@bot-gate.ru" class="hover:text-white transition">support@bot-gate.ru</a>
        </div>
    </div>
    <div class="border-t border-gray-800/60">
        <div class="max-w-6xl mx-auto px-4 sm:px-6 py-5 text-center text-xs text-gray-600">
            © {{ date('Y') }} BotGate — прокси для Telegram Bot API
        </div>
    </div>
</footer>

@include('partials.cookie-consent')
</body>
</html>
