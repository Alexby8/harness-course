@extends('layouts.app')
@section('title', 'Мои боты — BotGate')

@section('content')

{{-- Notification block --}}
@if($notification)
<div class="mb-6 rounded-lg border {{ $notification->type->colorClasses() }} px-4 py-3 flex items-start justify-between" id="notification-{{ $notification->id }}">
    <div class="flex-1">
        <h3 class="font-semibold mb-1">{{ $notification->title }}</h3>
        <p class="text-sm">{{ $notification->message }}</p>
    </div>
    <form method="POST" action="{{ route('notifications.dismiss', $notification->id) }}" class="ml-4">
        @csrf
        <button type="submit" class="cursor-pointer text-current opacity-60 hover:opacity-100 transition">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
            </svg>
        </button>
    </form>
</div>
@endif

{{-- Bots --}}
<div class="mb-6 flex flex-wrap items-center justify-between gap-4">
    <div>
        <h1 class="text-2xl font-bold tracking-tight">Мои боты</h1>
        <p class="mt-2 text-sm text-gray-400">Управляйте ботами и подключением вебхуков.</p>
    </div>
    <a href="{{ route('bots.create') }}"
       class="bg-indigo-600 hover:bg-indigo-500 text-white text-sm font-medium px-4 py-1.5 rounded-lg transition">
        + Добавить бота
    </a>
</div>

@forelse($bots as $bot)
<div class="mb-3 flex flex-wrap items-center justify-between gap-4 rounded-xl border border-gray-800 bg-gray-900 p-4 sm:p-5">
    <div class="min-w-0">
        <div class="mb-1 flex flex-wrap items-center gap-2">
            <span class="wrap-anywhere font-medium text-white">{{ $bot->name }}</span>
            @if($bot->status->isActive())
            <span class="text-xs bg-green-900/50 text-green-400 px-2 py-0.5 rounded-full">активен</span>
            @else
            <span class="text-xs bg-gray-700 text-gray-400 px-2 py-0.5 rounded-full">отключён</span>
            @endif
        </div>
        <p class="break-all text-xs text-gray-500">
            <span class="font-mono text-gray-400">{{ $bot->public_id }}</span>
            @if($bot->telegram_username)
            &nbsp;·&nbsp; {{ '@' . $bot->telegram_username }}
            @endif
        </p>
    </div>
    <div class="flex flex-wrap items-center gap-x-4 gap-y-2 text-sm">
        <a href="{{ route('dashboard.api-logs', ['bot' => $bot->public_id]) }}" class="text-gray-400 transition hover:text-white">API-логи</a>
        <a href="{{ route('dashboard.webhook-events', ['bot' => $bot->public_id]) }}" class="text-gray-400 transition hover:text-white">Webhook-события</a>
        <a href="{{ route('bots.show', $bot->public_id) }}" class="text-indigo-400 transition hover:text-indigo-300">Открыть →</a>
    </div>
</div>
@empty
<div class="text-center py-12 text-gray-500">
    <p class="mb-3">Ботов пока нет.</p>
    <a href="{{ route('bots.create') }}" class="text-indigo-400 hover:text-indigo-300 text-sm">Добавить первого бота →</a>
</div>
@endforelse

@endsection
