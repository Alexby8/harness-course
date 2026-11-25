@extends('layouts.app')
@section('title', 'Забыли пароль — BotGate')

@section('content')
<div class="max-w-md mx-auto mt-12">
    <h1 class="text-2xl font-bold mb-2">Забыли пароль?</h1>
    <p class="text-gray-400 text-sm mb-6">Введите ваш email — мы пришлём ссылку для сброса пароля.</p>

    <form method="POST" action="{{ route('password.email') }}" class="space-y-4">
        @csrf

        <div>
            <label class="block text-sm text-gray-400 mb-1">Email</label>
            <input type="email" name="email" value="{{ old('email') }}" required autofocus
                class="w-full bg-gray-800 border border-gray-700 rounded-lg px-3 py-2 text-sm text-white focus:outline-none focus:border-indigo-500 @error('email') border-red-500 @enderror">
            @error('email')
            <p class="mt-1 text-xs text-red-400">{{ $message }}</p>
            @enderror
        </div>

        <button type="submit"
            class="cursor-pointer w-full bg-indigo-600 hover:bg-indigo-500 text-white font-medium rounded-lg px-4 py-2 text-sm transition">
            Отправить ссылку
        </button>
    </form>

    <p class="mt-4 text-sm text-gray-500 text-center">
        <a href="{{ route('login') }}" class="text-indigo-400 hover:text-indigo-300">← Вернуться ко входу</a>
    </p>
</div>
@endsection
