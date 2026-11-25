@extends('layouts.app')
@section('title', 'Вход — BotGate')

@section('content')
<div class="max-w-md mx-auto mt-12">
    <h1 class="text-2xl font-bold mb-6">Вход в аккаунт</h1>

    <form method="POST" action="{{ route('login') }}" class="space-y-4">
        @csrf

        <div>
            <label class="block text-sm text-gray-400 mb-1">Email</label>
            <input type="email" name="email" value="{{ old('email') }}" required autofocus
                class="w-full bg-gray-800 border border-gray-700 rounded-lg px-3 py-2 text-sm text-white focus:outline-none focus:border-indigo-500 @error('email') border-red-500 @enderror">
            @error('email')
            <p class="mt-1 text-xs text-red-400">{{ $message }}</p>
            @enderror
        </div>

        <div>
            <label class="block text-sm text-gray-400 mb-1">Пароль</label>
            <input type="password" name="password" required
                class="w-full bg-gray-800 border border-gray-700 rounded-lg px-3 py-2 text-sm text-white focus:outline-none focus:border-indigo-500">
        </div>

        <div class="flex items-center justify-between">
            <div class="flex items-center gap-2">
                <input type="checkbox" name="remember" id="remember" class="rounded">
                <label for="remember" class="text-sm text-gray-400">Запомнить меня</label>
            </div>
            <a href="{{ route('password.request') }}" class="text-sm text-indigo-400 hover:text-indigo-300">Забыли пароль?</a>
        </div>

        <button type="submit"
            class="cursor-pointer w-full bg-indigo-600 hover:bg-indigo-500 text-white font-medium rounded-lg px-4 py-2 text-sm transition">
            Войти
        </button>
    </form>

    <p class="mt-4 text-sm text-gray-500 text-center">
        Нет аккаунта?
        <a href="{{ route('register') }}" class="text-indigo-400 hover:text-indigo-300">Зарегистрироваться</a>
    </p>
</div>
@endsection
