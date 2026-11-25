@extends('layouts.app')
@section('title', 'Подтверждение email — BotGate')

@section('content')
<div class="max-w-md mx-auto mt-12">

    <div class="bg-gray-900 border border-gray-800 rounded-xl p-8 text-center">

        <div class="w-14 h-14 bg-indigo-900/40 border border-indigo-800/50 rounded-xl flex items-center justify-center mx-auto mb-5 text-2xl">
            ✉️
        </div>

        <h1 class="text-xl font-bold text-white mb-2">Проверьте вашу почту</h1>
        <p class="text-sm text-gray-400 leading-relaxed mb-1">
            Мы отправили письмо с ссылкой для подтверждения на адрес:
        </p>
        <p class="text-sm text-indigo-400 font-medium mb-6">{{ Auth::user()->email }}</p>

        <p class="text-xs text-gray-500 leading-relaxed mb-8">
            Перейдите по ссылке в письме, чтобы активировать аккаунт и получить API ключ.
            Ссылка действует 24 часа.
        </p>

        <div class="border-t border-gray-800 pt-6">
            <p class="text-xs text-gray-500 mb-3">Письмо не пришло?</p>
            <form method="POST" action="{{ route('verification.send') }}">
                @csrf
                <button type="submit"
                    class="cursor-pointer bg-gray-800 hover:bg-gray-700 text-gray-300 hover:text-white text-sm font-medium px-5 py-2 rounded-lg transition">
                    Отправить повторно
                </button>
            </form>
        </div>

    </div>

    <div class="mt-4 text-center">
        <form method="POST" action="{{ route('logout') }}">
            @csrf
            <button class="cursor-pointer text-xs text-gray-600 hover:text-gray-400 transition">
                Выйти из аккаунта
            </button>
        </form>
    </div>

</div>
@endsection
