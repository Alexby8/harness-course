@extends('layouts.app')
@section('title', 'Регистрация — BotGate')

@section('content')
<div class="max-w-md mx-auto mt-12">
    <h1 class="text-2xl font-bold mb-6">Создать аккаунт</h1>

    <form method="POST" action="{{ route('register') }}" class="space-y-4">
        @csrf
        <input type="hidden" name="analytics_allowed" value="0" data-acquisition-allowed>

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
                class="w-full bg-gray-800 border border-gray-700 rounded-lg px-3 py-2 text-sm text-white focus:outline-none focus:border-indigo-500 @error('password') border-red-500 @enderror">
            @error('password')
            <p class="mt-1 text-xs text-red-400">{{ $message }}</p>
            @enderror
        </div>

        <div>
            <label class="block text-sm text-gray-400 mb-1">Подтвердите пароль</label>
            <input type="password" name="password_confirmation" required
                class="w-full bg-gray-800 border border-gray-700 rounded-lg px-3 py-2 text-sm text-white focus:outline-none focus:border-indigo-500">
        </div>

        <div>
            <div class="flex items-start gap-3">
                <input type="checkbox" name="terms" id="terms" value="1" {{ old('terms') ? 'checked' : '' }}
                    class="mt-0.5 shrink-0 rounded border-gray-600 bg-gray-800 text-indigo-600 focus:ring-indigo-500 @error('terms') border-red-500 @enderror">
                <label for="terms" class="text-sm text-gray-400 leading-snug">
                    Принимаю условия
                    <a href="/documents/BotGate_User_Agreement.pdf" target="_blank" class="text-indigo-400 hover:text-indigo-300 transition">пользовательского соглашения</a>, ознакомлен с
                    <a href="/documents/BotGate_Privacy_Policy.pdf" target="_blank" class="text-indigo-400 hover:text-indigo-300 transition">политикой обработки персональных данных</a>
                    и даю
                    <a href="/documents/BotGate_Consent_Personal_Data.pdf" target="_blank" class="text-indigo-400 hover:text-indigo-300 transition">согласие на обработку персональных данных</a>
                </label>
            </div>
            @error('terms')
            <p class="mt-1 text-xs text-red-400">{{ $message }}</p>
            @enderror
        </div>

        <button type="submit"
            class="cursor-pointer w-full bg-indigo-600 hover:bg-indigo-500 text-white font-medium rounded-lg px-4 py-2 text-sm transition">
            Создать аккаунт
        </button>
    </form>

    <p class="mt-4 text-sm text-gray-500 text-center">
        Уже есть аккаунт?
        <a href="{{ route('login') }}" class="text-indigo-400 hover:text-indigo-300">Войти</a>
    </p>
</div>
@endsection
