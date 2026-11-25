<!DOCTYPE html>
<html lang="ru" class="h-full bg-gray-950 scheme-dark">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex, nofollow">
    <title>@yield('title', $title ?? 'BotGate')</title>
    @include('partials.favicon')
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @auth
        @livewireStyles
    @endauth
</head>
<body class="min-h-screen text-gray-100 font-sans antialiased" @auth x-data="{ menuOpen: false }" :class="{ 'overflow-hidden': menuOpen }" @resize.window="if (window.innerWidth >= 1024) $refs.mobileMenu.close()" @endauth>

<header class="h-16 border-b border-gray-800 bg-gray-900/80">
    <div class="mx-auto flex h-full max-w-[1600px] items-center justify-between gap-3 px-4 sm:px-6 lg:px-8">
        <div class="flex min-w-0 items-center gap-3">
            @auth
                <button type="button" @click="menuOpen = true; $refs.mobileMenu.showModal()" :aria-expanded="menuOpen" aria-controls="dashboard-mobile-menu" aria-haspopup="dialog" aria-label="Открыть меню кабинета" class="flex h-10 w-10 shrink-0 cursor-pointer items-center justify-center rounded-lg text-gray-400 transition hover:bg-gray-800 hover:text-white lg:hidden">
                    <svg aria-hidden="true" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 6h16 M4 12h16 M4 18h16"/></svg>
                </button>
            @endauth
            <a href="{{ route('dashboard') }}" class="shrink-0 text-white font-bold text-lg tracking-tight">
                Bot<span class="text-indigo-400">Gate</span>
            </a>
        </div>
        @auth
        <div class="flex min-w-0 items-center gap-6 text-sm">
            <details class="account-menu relative min-w-0">
                <summary class="flex cursor-pointer list-none items-center gap-2 rounded-lg px-2 py-2 text-gray-300 transition hover:bg-gray-800 hover:text-white">
                    <span class="sm:hidden">Аккаунт</span>
                    <span class="hidden max-w-56 truncate sm:block">{{ Auth::user()->email }}</span>
                    <svg aria-hidden="true" class="h-4 w-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m6 9 6 6 6-6"/></svg>
                </summary>
                <div class="absolute right-0 z-30 mt-2 w-60 max-w-[calc(100vw-2rem)] rounded-xl border border-gray-700 bg-gray-900 p-2 shadow-xl">
                    <p class="break-all px-3 py-2 text-xs text-gray-500">{{ Auth::user()->email }}</p>
                    <a href="{{ route('profile.show') }}" class="block rounded-lg px-3 py-2 text-gray-300 hover:bg-gray-800 hover:text-white" @if(request()->routeIs('profile.*')) aria-current="page" @endif>Профиль</a>
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button class="w-full cursor-pointer rounded-lg px-3 py-2 text-left text-gray-300 hover:bg-gray-800 hover:text-white">Выйти</button>
                    </form>
                </div>
            </details>
        </div>
        @else
        <div class="flex gap-4 text-sm">
            <a href="{{ route('login') }}" class="text-gray-300 hover:text-white transition">Войти</a>
            <a href="{{ route('register') }}" class="text-indigo-400 hover:text-indigo-300 transition">Регистрация</a>
        </div>
        @endauth
    </div>
</header>

@auth
    <dialog id="dashboard-mobile-menu" x-ref="mobileMenu" @close="menuOpen = false" @click.self="$refs.mobileMenu.close()" aria-labelledby="dashboard-menu-title" class="fixed inset-y-0 left-0 m-0 h-dvh max-h-none w-72 max-w-[calc(100vw-2rem)] border-r border-gray-800 bg-gray-950 p-0 text-gray-100 backdrop:bg-gray-950/70 lg:hidden">
        <div class="flex h-full flex-col">
            <div class="flex h-16 shrink-0 items-center justify-between border-b border-gray-800 px-4">
                <h2 id="dashboard-menu-title" class="text-sm font-semibold">Меню кабинета</h2>
                <button type="button" autofocus @click="$refs.mobileMenu.close()" aria-label="Закрыть меню кабинета" class="flex h-10 w-10 cursor-pointer items-center justify-center rounded-lg text-gray-400 hover:bg-gray-800 hover:text-white">
                    <svg aria-hidden="true" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="m6 6 12 12 M6 18 18 6"/></svg>
                </button>
            </div>
            <div class="overflow-y-auto p-4">
                @include('partials.dashboard-navigation')
            </div>
        </div>
    </dialog>
@endauth

<div class="mx-auto @auth grid min-h-[calc(100dvh-4rem)] max-w-[1600px] gap-6 lg:grid-cols-[14rem_minmax(0,1fr)] lg:gap-8 @else max-w-5xl @endauth px-4 sm:px-6 lg:px-8">
    @auth
        <aside class="hidden border-r border-gray-800 pr-6 lg:block">
            <div class="sticky top-6 py-6">
                <p class="mb-3 px-3 text-xs font-medium uppercase tracking-wider text-gray-600">Кабинет</p>
                @include('partials.dashboard-navigation')
            </div>
        </aside>
    @endauth

<main class="min-w-0 py-6 sm:py-8">

    @if(session('success'))
    <div class="mb-6 rounded-lg bg-green-900/40 border border-green-700 px-4 py-3 text-green-300 text-sm">
        {{ session('success') }}
    </div>
    @endif

    @if(session('error'))
    <div class="mb-6 rounded-lg bg-red-900/40 border border-red-700 px-4 py-3 text-red-300 text-sm">
        {{ session('error') }}
    </div>
    @endif

    @yield('content')
    {{ $slot ?? '' }}
</main>
</div>

@auth
    @livewireScripts
@endauth
@include('partials.acquisition')
@include('partials.metrika-goal')
</body>
</html>
