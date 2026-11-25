<nav aria-label="Основная навигация" class="sticky top-0 z-30 bg-gray-950/95 backdrop-blur border-b border-gray-800/80">
    <div class="max-w-6xl mx-auto px-4 sm:px-6 h-16 grid grid-cols-[auto_1fr] lg:grid-cols-[minmax(0,1fr)_auto_minmax(0,1fr)] items-center gap-3 lg:gap-4">
        <a href="{{ route('home') }}" class="flex items-center gap-2 justify-self-start shrink-0">
            <img src="{{ asset('images/logo-mark.webp') }}" alt="" width="32" height="32" class="h-8 w-8">
            <span class="text-white font-bold text-lg tracking-tight">Bot<span class="text-indigo-400">Gate</span></span>
        </a>

        <div class="hidden lg:flex items-center justify-center gap-4 xl:gap-7 text-sm text-gray-400 whitespace-nowrap">
            @if($showHomeSections ?? false)
                <a href="#features" class="hover:text-white transition">Возможности</a>
                <a href="#how" class="hover:text-white transition">Как работает</a>
                <a href="#sdk" class="hover:text-white transition">SDK</a>
            @endif
            <a href="{{ route('docs') }}" class="hover:text-white transition">Документация</a>
            <a href="{{ route('guides.index') }}" class="hover:text-white transition" @if(request()->routeIs('guides.index')) aria-current="page" @endif>Руководства</a>
        </div>

        <div class="flex items-center justify-self-end gap-2 sm:gap-3 text-sm whitespace-nowrap">
            @auth
                <a href="{{ route('dashboard') }}" class="bg-indigo-600 hover:bg-indigo-500 text-white font-medium px-3 sm:px-4 py-2 rounded-lg transition">Личный кабинет</a>
            @else
                <a href="{{ route('login') }}" class="text-gray-300 hover:text-white transition">Войти</a>
                <a href="{{ route('register') }}" class="bg-indigo-600 hover:bg-indigo-500 text-white font-medium px-3 sm:px-4 py-2 rounded-lg transition shadow-lg shadow-indigo-600/20">
                    <span class="sm:hidden">Регистрация</span><span class="hidden sm:inline">Начать бесплатно</span>
                </a>
            @endauth
        </div>
    </div>
    <div class="lg:hidden flex items-center justify-center gap-6 border-t border-gray-800/80 px-4 py-2.5 text-sm text-gray-400">
        <a href="{{ route('docs') }}" class="hover:text-white transition">Документация</a>
        <a href="{{ route('guides.index') }}" class="hover:text-white transition" @if(request()->routeIs('guides.index')) aria-current="page" @endif>Руководства</a>
    </div>
</nav>
