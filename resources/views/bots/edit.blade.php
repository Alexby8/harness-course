@extends('layouts.app')
@section('title', 'Редактировать бота — BotGate')

@section('content')
<div class="max-w-lg mx-auto">
    <div class="flex items-center gap-3 mb-6">
        <a href="{{ route('bots.show', $bot->public_id) }}" class="text-gray-500 hover:text-gray-300 transition text-sm">← Назад</a>
    </div>

    <h1 class="text-2xl font-bold mb-6">Редактировать бота</h1>

    <form method="POST" action="{{ route('bots.update', $bot->public_id) }}" class="space-y-4">
        @csrf
        @method('PUT')

        <div>
            <label class="block text-sm text-gray-400 mb-1">Название бота</label>
            <input type="text" name="name" value="{{ old('name', $bot->name) }}" required
                class="w-full bg-gray-800 border border-gray-700 rounded-lg px-3 py-2 text-sm text-white focus:outline-none focus:border-indigo-500 @error('name') border-red-500 @enderror">
            @error('name')
            <p class="mt-1 text-xs text-red-400">{{ $message }}</p>
            @enderror
        </div>

        <div>
            <label class="block text-sm text-gray-400 mb-1">Новый Telegram Bot Token <span class="text-gray-600">(оставьте пустым, чтобы не менять)</span></label>
            <input type="text" name="token" value="{{ old('token') }}"
                placeholder="{{ $bot->masked_token }}"
                class="w-full bg-gray-800 border border-gray-700 rounded-lg px-3 py-2 text-sm font-mono text-white focus:outline-none focus:border-indigo-500 @error('token') border-red-500 @enderror">
            @error('token')
            <p class="mt-1 text-xs text-red-400">{{ $message }}</p>
            @enderror
        </div>

        <div class="pt-2 flex gap-3">
            <button type="submit"
                class="cursor-pointer bg-indigo-600 hover:bg-indigo-500 text-white font-medium rounded-lg px-5 py-2 text-sm transition">
                Сохранить
            </button>
            <a href="{{ route('bots.show', $bot->public_id) }}"
                class="bg-gray-700 hover:bg-gray-600 text-gray-200 font-medium rounded-lg px-5 py-2 text-sm transition">
                Отмена
            </a>
        </div>
    </form>
</div>
@endsection
