@extends('layouts.app')
@section('title', 'Добавить бота — BotGate')

@section('content')
<div class="max-w-lg mx-auto">
    <div class="flex items-center gap-3 mb-6">
        <a href="{{ route('dashboard') }}" class="text-gray-500 hover:text-gray-300 transition text-sm">← Мои боты</a>
    </div>

    <h1 class="text-2xl font-bold mb-6">Добавить Telegram-бота</h1>

    <form method="POST" action="{{ route('bots.store') }}" class="space-y-4">
        @csrf

        <div>
            <label class="block text-sm text-gray-400 mb-1">Название бота</label>
            <input type="text" name="name" value="{{ old('name') }}" required placeholder="Мой бот уведомлений"
                class="w-full bg-gray-800 border border-gray-700 rounded-lg px-3 py-2 text-sm text-white focus:outline-none focus:border-indigo-500 @error('name') border-red-500 @enderror">
            @error('name')
            <p class="mt-1 text-xs text-red-400">{{ $message }}</p>
            @enderror
        </div>

        <div>
            <label class="block text-sm text-gray-400 mb-1">Telegram Bot Token</label>
            <input type="text" name="token" value="{{ old('token') }}" required
                placeholder="Токен от BotFather"
                class="w-full bg-gray-800 border border-gray-700 rounded-lg px-3 py-2 text-sm font-mono text-white focus:outline-none focus:border-indigo-500 @error('token') border-red-500 @enderror">
            <p class="mt-1 text-xs text-gray-500">Получите токен у <a href="https://t.me/BotFather" target="_blank" class="text-indigo-400">@BotFather</a></p>
            @error('token')
            <p class="mt-1 text-xs text-red-400">{{ $message }}</p>
            @enderror
        </div>

        <div class="pt-2">
            <button type="submit"
                class="w-full bg-indigo-600 hover:bg-indigo-500 text-white font-medium rounded-lg px-4 py-2 text-sm transition">
                Добавить бота
            </button>
        </div>
    </form>
</div>
@endsection
