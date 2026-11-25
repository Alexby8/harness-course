@extends('layouts.app')
@section('title', 'Профиль — BotGate')

@section('content')
<div class="max-w-lg mx-auto">

    <h1 class="text-2xl font-bold mb-6">Профиль</h1>

    {{-- Информация об аккаунте --}}
    <div class="bg-gray-900 border border-gray-800 rounded-xl p-5 mb-6">
        <h2 class="font-semibold text-gray-200 mb-4">Аккаунт</h2>
        <div>
            <label class="block text-xs text-gray-500 mb-1">Email</label>
            <p class="break-all text-sm text-gray-300">{{ $user->email }}</p>
        </div>
    </div>

    {{-- Смена пароля --}}
    <div class="bg-gray-900 border border-gray-800 rounded-xl p-5">
        <h2 class="font-semibold text-gray-200 mb-4">Сменить пароль</h2>

        <form method="POST" action="{{ route('profile.change-password') }}" class="space-y-4">
            @csrf

            <div>
                <label class="block text-sm text-gray-400 mb-1">Текущий пароль</label>
                <input type="password" name="current_password" autocomplete="current-password"
                    class="w-full bg-gray-800 border border-gray-700 rounded-lg px-3 py-2 text-sm text-white focus:outline-none focus:border-indigo-500 @error('current_password') border-red-500 @enderror">
                @error('current_password')
                <p class="mt-1 text-xs text-red-400">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label class="block text-sm text-gray-400 mb-1">Новый пароль</label>
                <input type="password" name="password" autocomplete="new-password"
                    class="w-full bg-gray-800 border border-gray-700 rounded-lg px-3 py-2 text-sm text-white focus:outline-none focus:border-indigo-500 @error('password') border-red-500 @enderror">
                @error('password')
                <p class="mt-1 text-xs text-red-400">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label class="block text-sm text-gray-400 mb-1">Повторите новый пароль</label>
                <input type="password" name="password_confirmation" autocomplete="new-password"
                    class="w-full bg-gray-800 border border-gray-700 rounded-lg px-3 py-2 text-sm text-white focus:outline-none focus:border-indigo-500">
            </div>

            <div class="pt-1">
                <button type="submit"
                    class="cursor-pointer bg-indigo-600 hover:bg-indigo-500 text-white text-sm font-medium px-5 py-2 rounded-lg transition">
                    Сохранить
                </button>
            </div>
        </form>
    </div>

</div>
@endsection
