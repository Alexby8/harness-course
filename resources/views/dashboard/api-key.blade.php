@extends('layouts.app')
@section('title', 'API-ключ — BotGate')

@section('content')
<div class="max-w-2xl">
    <h1 class="mb-2 text-2xl font-bold tracking-tight">API-ключ</h1>
    <p class="mb-6 text-sm text-gray-400">Один ключ для доступа к API всех ваших ботов.</p>

    <div class="rounded-xl border border-gray-800 bg-gray-900 p-5 sm:p-6">
        @if(session('new_api_key'))
            <div class="mb-5 rounded-lg border border-indigo-700 bg-indigo-900/40 px-4 py-3">
                <p class="mb-2 text-xs text-indigo-300">Ваш API-ключ — скопируйте его сейчас, он больше не будет показан:</p>
                <code class="select-all break-all font-mono text-sm text-white">{{ session('new_api_key') }}</code>
            </div>
        @endif

        @if($user->api_key_hash)
            <p class="mb-2 text-sm font-medium text-gray-200">Текущий ключ</p>
            <code class="break-all font-mono text-sm text-gray-300">{{ $user->api_key_preview }}</code>
            <p class="mt-3 text-sm text-gray-400">Используйте ключ в заголовке <code class="break-all text-indigo-300">Authorization: Bearer &lt;key&gt;</code>.</p>
            <div class="mt-6 border-t border-gray-800 pt-5">
                <p class="mb-3 text-sm text-gray-500">При перевыпуске старый ключ перестанет работать немедленно.</p>
                <form method="POST" action="{{ route('api-key.reissue') }}" onsubmit="return confirm('Перевыпустить API-ключ? Старый ключ перестанет работать немедленно.')">
                    @csrf
                    <button class="cursor-pointer rounded-lg border border-gray-700 px-4 py-2 text-sm font-medium text-gray-300 transition hover:border-red-500 hover:text-red-400">Перевыпустить ключ</button>
                </form>
            </div>
        @else
            <p class="mb-4 text-sm text-gray-400">API-ключ ещё не создан. Создайте его, чтобы начать использовать API.</p>
            <form method="POST" action="{{ route('api-key.reissue') }}">
                @csrf
                <button class="cursor-pointer rounded-lg bg-indigo-600 px-4 py-2 text-sm font-medium text-white transition hover:bg-indigo-500">Создать ключ</button>
            </form>
        @endif
    </div>
</div>
@endsection
