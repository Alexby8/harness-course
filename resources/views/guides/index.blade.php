@extends('guides.layout')

@section('title', 'Руководства по Telegram Bot API')
@section('description', 'Практические руководства BotGate: создание и запуск Telegram-бота, сценарии для бизнеса, диагностика ошибок Bot API и подключение приложений.')
@section('canonical_path', $canonicalPath)

@section('content')
<header class="max-w-3xl pt-12 pb-10 sm:pt-20 sm:pb-14">
    <p class="mb-4 text-xs font-semibold uppercase tracking-widest text-indigo-400">Практика работы с Telegram Bot API</p>
    <h1 class="text-4xl font-bold tracking-tight text-white sm:text-5xl">Руководства</h1>
    <p class="mt-5 max-w-2xl text-lg leading-relaxed text-gray-400">От создания первого бота и планирования диалога до подключения приложений и диагностики ошибок. С пошаговыми инструкциями, примерами и объяснениями.</p>
</header>

<section id="guide-catalog" aria-labelledby="more-guides">
    <h2 id="more-guides" class="mb-5 text-sm font-medium text-gray-300">Все руководства</h2>
    <div class="grid gap-5 md:grid-cols-2">
        @forelse($guides as $guide)
            <a href="{{ $guide->url() }}" class="group flex flex-col rounded-2xl border border-gray-800 bg-gray-900/40 p-6 transition hover:border-indigo-500/50 focus-visible:outline-2 focus-visible:outline-offset-4 focus-visible:outline-indigo-400 sm:p-8">
                <div class="mb-5 flex flex-wrap items-center gap-3 text-xs text-gray-500">
                    <span class="rounded-full border border-indigo-500/20 bg-indigo-500/10 px-3 py-1 text-indigo-300">{{ $guide['category'] }}</span>
                    <span>{{ $guide['reading_time'] }} чтения</span>
                </div>
                <h3 class="text-xl font-semibold leading-snug tracking-tight text-white transition group-hover:text-indigo-200 sm:text-2xl">{{ $guide['title'] }}</h3>
                <p class="mt-4 mb-7 text-sm leading-7 text-gray-400">{{ $guide->card_description ?: $guide->description }}</p>
                <div class="mt-auto flex flex-wrap items-center justify-between gap-4">
                    <span class="text-sm font-medium text-indigo-300">Читать руководство <span aria-hidden="true">→</span></span>
                    <time datetime="{{ $guide->published_at->format('Y-m-d') }}" class="text-xs text-gray-500">{{ $guide->published_at->locale('ru')->translatedFormat('j F Y') }}</time>
                </div>
            </a>
        @empty
            <p class="text-gray-400">Руководства пока не опубликованы.</p>
        @endforelse
    </div>
</section>
{{ $guides->links('guides.partials.pagination') }}
<aside class="mt-8 flex flex-col justify-between gap-4 rounded-xl border border-gray-800/80 px-6 py-5 sm:flex-row sm:items-center">
    <div>
        <h2 class="text-sm font-medium text-gray-200">Нужны параметры запросов и примеры подключения?</h2>
        <p class="mt-1 text-sm text-gray-500">Справочник методов, авторизация и webhook — в документации.</p>
    </div>
    <a href="{{ route('docs') }}" class="shrink-0 text-sm text-indigo-400 hover:text-indigo-300 transition">Открыть документацию →</a>
</aside>
@endsection
