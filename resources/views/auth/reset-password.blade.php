@extends('layouts.app')
@section('title', 'Сброс пароля — BotGate')

@section('content')
<div class="max-w-md mx-auto mt-12">
    <h1 class="text-2xl font-bold mb-6">Новый пароль</h1>

    <form method="POST" action="{{ route('password.update') }}" class="space-y-4">
        @csrf
        <input type="hidden" name="token" value="{{ $token }}">

        <div>
            <label class="block text-sm text-gray-400 mb-1">Email</label>
            <input type="email" name="email" value="{{ old('email', $email) }}" required
                class="w-full bg-gray-800 border border-gray-700 rounded-lg px-3 py-2 text-sm text-white focus:outline-none focus:border-indigo-500 @error('email') border-red-500 @enderror">
            @error('email')
            <p class="mt-1 text-xs text-red-400">{{ $message }}</p>
            @enderror
        </div>

        <div>
            <label class="block text-sm text-gray-400 mb-1">Новый пароль</label>
            <input type="password" name="password" required autofocus
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

        <button type="submit"
            class="cursor-pointer w-full bg-indigo-600 hover:bg-indigo-500 text-white font-medium rounded-lg px-4 py-2 text-sm transition">
            Сохранить пароль
        </button>
    </form>
</div>
@endsection
