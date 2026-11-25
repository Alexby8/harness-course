@include('partials.acquisition')
<div data-cookie-consent
     data-endpoint="{{ route('cookie-consent.store', absolute: false) }}"
     data-version="{{ config('cookie-consent.version') }}"
     data-csrf="{{ csrf_token() }}"
     data-metrika-id="{{ app()->isProduction() && request()->routeIs('home', 'docs', 'guides.*') ? config('botgate.yandex_metrika_id') : '' }}">
    <div class="px-4 py-4 text-center text-xs text-gray-400">
        <button type="button" data-cookie-open hidden aria-controls="cookie-banner" aria-expanded="false"
                class="cursor-pointer underline underline-offset-4 hover:text-white focus-visible:outline-2 focus-visible:outline-indigo-400">Cookies</button>
        <noscript>Для изменения выбора cookies включите JavaScript.</noscript>
    </div>
    <section id="cookie-banner" data-cookie-banner hidden aria-label="Использование cookies"
             class="fixed left-0 top-[100dvh] z-50 max-h-dvh w-screen -translate-y-full overflow-y-auto border-t border-gray-700 bg-gray-900 px-4 py-5 text-sm text-gray-300 shadow-2xl sm:px-6">
        <div class="mx-auto flex max-w-6xl flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <p class="max-w-3xl leading-relaxed">
                Используем cookies для работы сайта и анализа посещаемости. Метрику и рекламную атрибуцию можно отключить в настройках cookies.
                <a href="/documents/BotGate_Analytics_Consent.pdf" target="_blank" rel="noopener"
                   class="text-indigo-300 underline underline-offset-4 hover:text-indigo-200">Подробнее</a>
            </p>
            <div class="flex shrink-0 flex-wrap items-center gap-x-5 gap-y-2">
                <button type="button" data-cookie-accept
                        class="cursor-pointer rounded-lg bg-indigo-600 px-5 py-2.5 font-medium text-white transition hover:bg-indigo-500 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-indigo-400 disabled:opacity-60">Согласен</button>
                <button type="button" data-cookie-settings aria-controls="cookie-settings" aria-expanded="false"
                        class="cursor-pointer py-2.5 text-gray-300 underline underline-offset-4 hover:text-white focus-visible:outline-2 focus-visible:outline-indigo-400">Настройки</button>
            </div>
        </div>
        <div id="cookie-settings" data-cookie-settings-panel hidden class="mx-auto mt-4 max-w-6xl border-t border-gray-700 pt-4">
            <p data-cookie-status class="font-medium" aria-live="polite"></p>
            <p class="mt-2 leading-relaxed">Яндекс Метрика и сохранение источника перехода включены по умолчанию, если вы ранее их не отключили. При регистрации источник может быть связан с аккаунтом. Нажатие «Согласен» сохраняет ваше согласие с этого момента. Необходимые cookies и внутренний счётчик просмотров руководств работают независимо от этой настройки.</p>
            <div class="mt-3 flex flex-wrap items-center gap-5">
                <button type="button" data-cookie-decline class="cursor-pointer py-2.5 text-gray-300 underline underline-offset-4 hover:text-white focus-visible:outline-2 focus-visible:outline-indigo-400 disabled:opacity-60">Отключить Метрику и рекламную атрибуцию</button>
                <button type="button" data-cookie-dismiss class="cursor-pointer py-2.5 text-gray-300 underline underline-offset-4 hover:text-white focus-visible:outline-2 focus-visible:outline-indigo-400">Закрыть</button>
            </div>
        </div>
        <p data-cookie-error hidden role="alert" class="mx-auto mt-3 max-w-6xl text-red-300"></p>
    </section>
</div>
