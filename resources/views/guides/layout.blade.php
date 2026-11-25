<!DOCTYPE html>
<html lang="ru" class="scroll-smooth bg-gray-950">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title') — BotGate</title>
    <meta name="description" content="@yield('description')">
    <link rel="canonical" href="{{ rtrim(config('app.url'), '/') }}@yield('canonical_path')">
    <meta name="robots" content="index, follow">
    <meta name="theme-color" content="#030712">
    <meta property="og:type" content="@yield('og_type', 'website')">
    <meta property="og:site_name" content="BotGate">
    <meta property="og:locale" content="ru_RU">
    <meta property="og:title" content="@yield('title') — BotGate">
    <meta property="og:description" content="@yield('description')">
    <meta property="og:url" content="{{ rtrim(config('app.url'), '/') }}@yield('canonical_path')">
    <meta property="og:image" content="{{ rtrim(config('app.url'), '/') }}/images/og-image.png">
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="@yield('title') — BotGate">
    <meta name="twitter:description" content="@yield('description')">
    <meta name="twitter:image" content="{{ rtrim(config('app.url'), '/') }}/images/og-image.png">
    @include('partials.favicon')
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-gray-950 text-gray-100 font-sans antialiased selection:bg-indigo-500/30">
<a href="#content" class="sr-only focus:not-sr-only focus:absolute focus:top-2 focus:left-4 focus:z-50 focus:rounded-lg focus:bg-indigo-600 focus:px-4 focus:py-2">Перейти к содержанию</a>
@include('partials.site-navigation')
<main id="content" class="mx-auto max-w-6xl px-4 sm:px-6">
    @yield('content')
</main>
<footer class="mt-16 border-t border-gray-800/80">
    <div class="mx-auto flex max-w-6xl flex-wrap items-center justify-between gap-4 px-4 py-8 text-sm text-gray-500 sm:px-6">
        <a href="{{ route('home') }}" class="hover:text-gray-300 transition">© {{ date('Y') }} BotGate</a>
        <div class="flex gap-5">
            <a href="{{ route('docs') }}" class="hover:text-gray-300 transition">Документация API</a>
            <a href="mailto:support@bot-gate.ru" class="hover:text-gray-300 transition">Поддержка</a>
        </div>
    </div>
</footer>
@include('partials.cookie-consent')
</body>
</html>
