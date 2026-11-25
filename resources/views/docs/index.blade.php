<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Документация Telegram Bot API: подключение, SDK и webhook — BotGate</title>
    <meta name="description" content="Как подключить Telegram-бота к BotGate: API-ключ, примеры запросов, SDK для PHP и Laravel, отправка и скачивание файлов, настройка webhook и коды ошибок.">
    <link rel="canonical" href="{{ rtrim(config('app.url'), '/') }}/docs">
    <meta name="robots" content="index, follow">
    <meta name="theme-color" content="#030712">
    <meta property="og:type" content="website">
    <meta property="og:site_name" content="BotGate">
    <meta property="og:locale" content="ru_RU">
    <meta property="og:title" content="Документация Telegram Bot API — BotGate">
    <meta property="og:description" content="Подключение бота, SDK для PHP и Laravel, примеры API-запросов, работа с файлами и webhook.">
    <meta property="og:url" content="{{ rtrim(config('app.url'), '/') }}/docs">
    <meta property="og:image" content="{{ rtrim(config('app.url'), '/') }}/images/og-image.png">
    <meta property="og:image:width" content="1536">
    <meta property="og:image:height" content="1024">
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="Документация Telegram Bot API — BotGate">
    <meta name="twitter:description" content="Подключение бота, SDK для PHP и Laravel, примеры API-запросов, работа с файлами и webhook.">
    <meta name="twitter:image" content="{{ rtrim(config('app.url'), '/') }}/images/og-image.png">
    @include('partials.favicon')
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="h-full bg-gray-950 text-gray-100 font-sans antialiased">

<nav class="bg-gray-900 border-b border-gray-800 sticky top-0 z-10">
    <div class="max-w-5xl mx-auto px-4 py-3 flex items-center justify-between gap-4">
        <div class="flex items-center gap-6">
            <a href="/" class="shrink-0 text-white font-bold text-lg tracking-tight">
                Bot<span class="text-indigo-400">Gate</span>
            </a>
            <div class="hidden lg:flex items-center gap-4 text-sm text-gray-400 whitespace-nowrap">
                <a href="#sdk" class="hover:text-white transition">SDK</a>
                <a href="#auth" class="hover:text-white transition">Аутентификация</a>
                <a href="#proxy" class="hover:text-white transition">Прокси API</a>
                <a href="#files" class="hover:text-white transition">Файлы</a>
                <a href="#webhook" class="hover:text-white transition">Webhook</a>
                <a href="#errors" class="hover:text-white transition">Ошибки</a>
                <a href="#support" class="hover:text-white transition">Поддержка</a>
            </div>
        </div>
        @auth
        <div class="flex shrink-0 items-center gap-4 text-sm">
            <span class="hidden xl:block max-w-40 truncate text-gray-400" title="{{ Auth::user()->email }}">{{ Auth::user()->email }}</span>
            <a href="{{ route('dashboard') }}" class="text-indigo-400 hover:text-indigo-300 transition">Кабинет</a>
        </div>
        @else
        <div class="flex shrink-0 gap-4 text-sm">
            <a href="{{ route('login') }}" class="text-gray-300 hover:text-white transition">Войти</a>
            <a href="{{ route('register') }}" class="text-indigo-400 hover:text-indigo-300 transition">Регистрация</a>
        </div>
        @endauth
    </div>
</nav>

<main class="max-w-5xl mx-auto px-4 py-8 space-y-20">

    {{-- Hero --}}
    <section class="text-center space-y-4">
        <h1 class="text-4xl font-bold text-white">Документация BotGate API</h1>
        <p class="text-gray-400 text-lg max-w-2xl mx-auto">
            BotGate — прокси-сервис для Telegram Bot API. Он позволяет отправлять запросы к Telegram через
            единый шлюз с авторизацией по API-ключу, логированием и поддержкой webhook.
        </p>
        <div class="flex justify-center gap-3 pt-2">
            <a href="#quick-start"
               class="bg-indigo-600 hover:bg-indigo-500 text-white text-sm font-medium px-5 py-2 rounded-lg transition">
                Быстрый старт
            </a>
            <a href="{{ route('register') }}"
               class="bg-gray-800 hover:bg-gray-700 text-gray-200 text-sm font-medium px-5 py-2 rounded-lg transition">
                Создать аккаунт
            </a>
        </div>
    </section>

    {{-- Быстрый старт --}}
    <section id="quick-start" class="space-y-6">
        <h2 class="text-2xl font-semibold text-white border-b border-gray-800 pb-3">Быстрый старт</h2>
        <ol class="list-decimal list-inside space-y-2 text-gray-300 text-sm leading-relaxed">
            <li>Зарегистрируйтесь и войдите в <a href="{{ route('dashboard') }}" class="text-indigo-400 hover:underline">личный кабинет</a>.</li>
            <li>Добавьте бота: нажмите «Добавить бота», введите имя и токен от <a href="https://t.me/BotFather" class="text-indigo-400 hover:underline" target="_blank">@BotFather</a>.</li>
            <li>Скопируйте ваш API-ключ вида <code class="text-pink-400">bg_live_...</code> со страницы дашборда.</li>
            <li>Используйте URL прокси вместо стандартного <code class="text-pink-400">api.telegram.org</code>.</li>
        </ol>
        <p class="text-sm text-gray-400">
            Не хотите формировать запросы вручную? Установите готовый
            <a href="#sdk" class="text-indigo-400 hover:underline">SDK для PHP или Laravel</a>.
        </p>
    </section>

    {{-- SDK --}}
    <section id="sdk" class="space-y-6">
        <h2 class="text-2xl font-semibold text-white border-b border-gray-800 pb-3">Официальные SDK</h2>
        <p class="text-gray-400 text-sm">
            Чтобы не формировать HTTP-запросы вручную, используйте готовые пакеты. Они дают типизированный клиент
            для вызова методов Bot API, проверку подписи вебхуков и разбор входящих обновлений.
        </p>

        <div class="grid sm:grid-cols-2 gap-4">
            {{-- PHP SDK --}}
            <div class="rounded-lg border border-gray-800 bg-gray-900/40 p-5 space-y-3">
                <div class="flex items-center justify-between">
                    <h3 class="text-white font-medium">PHP</h3>
                    <a href="https://github.com/Alexby8/botgate-php-sdk" target="_blank" rel="noopener"
                       class="inline-flex items-center gap-1.5 text-xs text-indigo-400 hover:text-indigo-300 transition">
                        <svg class="h-4 w-4" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path fill-rule="evenodd" clip-rule="evenodd" d="M12 .5C5.37.5 0 5.78 0 12.292c0 5.211 3.438 9.63 8.205 11.188.6.111.82-.254.82-.567 0-.28-.01-1.022-.015-2.005-3.338.711-4.042-1.582-4.042-1.582-.546-1.361-1.335-1.725-1.335-1.725-1.087-.731.084-.716.084-.716 1.205.082 1.838 1.215 1.838 1.215 1.07 1.803 2.809 1.282 3.495.981.108-.763.417-1.282.76-1.577-2.665-.295-5.466-1.309-5.466-5.827 0-1.287.465-2.339 1.235-3.164-.135-.298-.54-1.497.105-3.121 0 0 1.005-.316 3.3 1.209a11.5 11.5 0 0 1 3-.398c1.02.006 2.04.136 3 .398 2.28-1.525 3.285-1.209 3.285-1.209.645 1.624.24 2.823.12 3.121.765.825 1.23 1.877 1.23 3.164 0 4.53-2.805 5.527-5.475 5.817.42.354.81 1.077.81 2.182 0 1.578-.015 2.846-.015 3.229 0 .309.21.678.825.561C20.565 21.917 24 17.495 24 12.292 24 5.78 18.627.5 12 .5z"/></svg>
                        GitHub
                    </a>
                </div>
                <p class="text-sm text-gray-400">Не зависит от фреймворка. Требуется PHP 8.2+ и PSR-18 HTTP-клиент.</p>
            </div>

            {{-- Laravel --}}
            <div class="rounded-lg border border-gray-800 bg-gray-900/40 p-5 space-y-3">
                <div class="flex items-center justify-between">
                    <h3 class="text-white font-medium">Laravel</h3>
                    <a href="https://github.com/Alexby8/botgate-laravel" target="_blank" rel="noopener"
                       class="inline-flex items-center gap-1.5 text-xs text-indigo-400 hover:text-indigo-300 transition">
                        <svg class="h-4 w-4" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path fill-rule="evenodd" clip-rule="evenodd" d="M12 .5C5.37.5 0 5.78 0 12.292c0 5.211 3.438 9.63 8.205 11.188.6.111.82-.254.82-.567 0-.28-.01-1.022-.015-2.005-3.338.711-4.042-1.582-4.042-1.582-.546-1.361-1.335-1.725-1.335-1.725-1.087-.731.084-.716.084-.716 1.205.082 1.838 1.215 1.838 1.215 1.07 1.803 2.809 1.282 3.495.981.108-.763.417-1.282.76-1.577-2.665-.295-5.466-1.309-5.466-5.827 0-1.287.465-2.339 1.235-3.164-.135-.298-.54-1.497.105-3.121 0 0 1.005-.316 3.3 1.209a11.5 11.5 0 0 1 3-.398c1.02.006 2.04.136 3 .398 2.28-1.525 3.285-1.209 3.285-1.209.645 1.624.24 2.823.12 3.121.765.825 1.23 1.877 1.23 3.164 0 4.53-2.805 5.527-5.475 5.817.42.354.81 1.077.81 2.182 0 1.578-.015 2.846-.015 3.229 0 .309.21.678.825.561C20.565 21.917 24 17.495 24 12.292 24 5.78 18.627.5 12 .5z"/></svg>
                        GitHub
                    </a>
                </div>
                <p class="text-sm text-gray-400">Интеграция SDK с Laravel 11–13: фасад, маршрут вебхука и события. Для Laravel 13 нужны botgate/laravel 1.1.0+ и PHP 8.3+.</p>
            </div>
        </div>

        <div class="bg-blue-950/40 border border-blue-800/40 rounded-lg px-4 py-3 text-sm text-blue-300">
            💡 Полные примеры (установка, вебхуки, загрузка файлов, обработка ошибок) — в README пакетов на GitHub.
        </div>
    </section>

    {{-- Аутентификация --}}
    <section id="auth" class="space-y-6">
        <h2 class="text-2xl font-semibold text-white border-b border-gray-800 pb-3">Аутентификация</h2>
        <p class="text-gray-400 text-sm">
            Все запросы к Proxy API должны содержать заголовок <code class="text-pink-400">Authorization</code>
            с API-ключом пользователя в формате Bearer.
        </p>
        <x-docs-code-block label="HTTP заголовок">
Authorization: Bearer bg_live_xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx
        </x-docs-code-block>
        <div class="bg-yellow-950/40 border border-yellow-800/40 rounded-lg px-4 py-3 text-sm text-yellow-300">
            ⚠️ Храните API-ключ в секрете. В случае компрометации пересоздайте его в личном кабинете.
        </div>
    </section>

    {{-- Прокси API --}}
    <section id="proxy" class="space-y-8">
        <h2 class="text-2xl font-semibold text-white border-b border-gray-800 pb-3">Прокси Telegram Bot API</h2>

        <div class="space-y-3">
            <h3 class="text-lg font-medium text-white">Формат запроса</h3>
            <x-docs-code-block label="URL">
{{ $baseUrl }}/v1/bots/{botPublicId}/{method}
            </x-docs-code-block>
            <ul class="text-sm text-gray-400 space-y-1 list-disc list-inside">
                <li><code class="text-pink-400">{botPublicId}</code> — идентификатор бота вида <code class="text-gray-300">bot_xxxxxxxxxxxx</code>, отображается в карточке бота.</li>
                <li><code class="text-pink-400">{method}</code> — любой метод Telegram Bot API, например <code class="text-gray-300">sendMessage</code>.</li>
                <li>Метод <code class="text-pink-400">getUpdates</code> заблокирован — используйте webhook.</li>
            </ul>
        </div>

        <div class="space-y-3">
            <h3 class="text-lg font-medium text-white">Пример: отправка сообщения</h3>
            <x-docs-code-block label="curl">
curl -X POST {{ $baseUrl }}/v1/bots/bot_xxxxxxxxxxxx/sendMessage \
  -H "Authorization: Bearer bg_live_xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx" \
  -H "Content-Type: application/json" \
  -d '{
    "chat_id": 123456789,
    "text": "Привет от BotGate!",
    "parse_mode": "HTML"
  }'
            </x-docs-code-block>
        </div>

        <div class="space-y-3">
            <h3 class="text-lg font-medium text-white">Пример: отправка фото</h3>
            <x-docs-code-block label="curl (multipart)">
curl -X POST {{ $baseUrl }}/v1/bots/bot_xxxxxxxxxxxx/sendPhoto \
  -H "Authorization: Bearer bg_live_xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx" \
  -F "chat_id=123456789" \
  -F "photo=@/path/to/photo.jpg" \
  -F "caption=Подпись к фото"
            </x-docs-code-block>
        </div>

        <div class="space-y-3">
            <h3 class="text-lg font-medium text-white">Пример ответа</h3>
            <x-docs-code-block label="JSON">
{
  "ok": true,
  "result": {
    "message_id": 42,
    "from": { "id": 987654321, "is_bot": true, "first_name": "MyBot", "username": "my_bot" },
    "chat": { "id": 123456789, "type": "private" },
    "date": 1700000000,
    "text": "Привет от BotGate!"
  }
}
            </x-docs-code-block>
        </div>

        <div class="space-y-3">
            <h3 class="text-lg font-medium text-white">Rate Limiting</h3>
            <p class="text-sm text-gray-400">
                Каждый API-ключ ограничен <strong class="text-white">600 запросами в минуту</strong>,
                каждый отдельный бот — <strong class="text-white">300 запросами в минуту</strong>.
                При превышении возвращается HTTP <code class="text-pink-400">429</code>.
            </p>
        </div>
    </section>

    {{-- Файлы --}}
    <section id="files" class="space-y-8">
        <h2 class="text-2xl font-semibold text-white border-b border-gray-800 pb-3">Скачивание файлов</h2>
        <p class="text-sm text-gray-400">
            Telegram не позволяет скачать файл напрямую без токена бота. BotGate проксирует скачивание,
            чтобы вам не нужно было хранить токен на клиентской стороне.
        </p>

        <div class="space-y-3">
            <h3 class="text-lg font-medium text-white">Шаг 1 — получить file_path</h3>
            <p class="text-sm text-gray-400">
                Вызовите метод <code class="text-pink-400">getFile</code>, передав <code class="text-pink-400">file_id</code>
                из любого объекта (фото, документ, голосовое и т.д.). В ответе вернётся <code class="text-pink-400">file_path</code>.
            </p>
            <x-docs-code-block label="curl">
curl -X POST {{ $baseUrl }}/v1/bots/bot_xxxxxxxxxxxx/getFile \
  -H "Authorization: Bearer bg_live_xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx" \
  -H "Content-Type: application/json" \
  -d '{"file_id": "BQACAgIAAxkBAAIBs2..."}'
            </x-docs-code-block>
            <x-docs-code-block label="Ответ">
{
  "ok": true,
  "result": {
    "file_id": "BQACAgIAAxkBAAIBs2...",
    "file_size": 14987,
    "file_path": "documents/file_123.pdf"
  }
}
            </x-docs-code-block>
        </div>

        <div class="space-y-3">
            <h3 class="text-lg font-medium text-white">Шаг 2 — скачать файл</h3>
            <p class="text-sm text-gray-400">
                Используйте полученный <code class="text-pink-400">file_path</code> в URL запроса на скачивание.
                Ответ — бинарный поток файла с корректным <code class="text-pink-400">Content-Type</code>.
            </p>
            <x-docs-code-block label="URL">
GET {{ $baseUrl }}/v1/bots/{botPublicId}/files/{file_path}
            </x-docs-code-block>
            <x-docs-code-block label="curl">
curl -O -J \
  -H "Authorization: Bearer bg_live_xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx" \
  "{{ $baseUrl }}/v1/bots/bot_xxxxxxxxxxxx/files/documents/file_123.pdf"
            </x-docs-code-block>
        </div>

        <div class="bg-blue-950/40 border border-blue-800/40 rounded-lg px-4 py-3 text-sm text-blue-300">
            💡 Файлы в Telegram доступны ограниченное время. Не кешируйте <code class="text-blue-200">file_path</code> дольше нескольких часов — запрашивайте актуальный через <code class="text-blue-200">getFile</code> при необходимости.
        </div>
    </section>

    {{-- Webhook --}}
    <section id="webhook" class="space-y-8">
        <h2 class="text-2xl font-semibold text-white border-b border-gray-800 pb-3">Webhook</h2>
        <p class="text-sm text-gray-400">
            BotGate может принимать обновления от Telegram и доставлять их на ваш сервер.
            Для этого укажите <strong class="text-white">Webhook URL</strong> в настройках бота.
        </p>

        <div class="space-y-3">
            <h3 class="text-lg font-medium text-white">Как это работает</h3>
            <ol class="text-sm text-gray-400 list-decimal list-inside space-y-1 leading-relaxed">
                <li>Telegram отправляет обновление на BotGate.</li>
                <li>BotGate сохраняет событие и помещает задачу в очередь.</li>
                <li>BotGate делает <code class="text-pink-400">POST</code> запрос на ваш Webhook URL.</li>
                <li>При неудаче — до 3 попыток с задержками 0, 60 и 300 секунд.</li>
            </ol>
        </div>

        <div class="space-y-3">
            <h3 class="text-lg font-medium text-white">Заголовки входящего запроса</h3>
            <x-docs-code-block label="HTTP Headers">
Content-Type: application/json
User-Agent: BotGate Webhook
X-BotGate-Bot-Id: bot_xxxxxxxxxxxx
X-BotGate-Event-Id: 42
X-BotGate-Signature: sha256_hmac_hex_signature
            </x-docs-code-block>
        </div>

        <div class="space-y-3">
            <h3 class="text-lg font-medium text-white">Проверка подписи</h3>
            <p class="text-sm text-gray-400">
                Заголовок <code class="text-pink-400">X-BotGate-Signature</code> — это HMAC-SHA256 от тела запроса,
                подписанный секретом вашего бота (<code class="text-pink-400">webhook_secret</code>).
                Секрет отображается в карточке бота.
            </p>
            <x-docs-code-block label="PHP — проверка подписи">
$payload  = file_get_contents('php://input');
$secret   = 'ваш_webhook_secret';
$expected = hash_hmac('sha256', $payload, $secret);
$received = $_SERVER['HTTP_X_BOTGATE_SIGNATURE'] ?? '';

if (!hash_equals($expected, $received)) {
    http_response_code(403);
    exit('Invalid signature');
}

$update = json_decode($payload, true);
// обрабатываем $update...
            </x-docs-code-block>
        </div>

        <div class="space-y-3">
            <h3 class="text-lg font-medium text-white">Пример payload</h3>
            <x-docs-code-block label="JSON">
{
  "update_id": 100500,
  "message": {
    "message_id": 1,
    "from": { "id": 123456789, "first_name": "Ivan", "username": "ivan" },
    "chat": { "id": 123456789, "type": "private" },
    "date": 1700000000,
    "text": "/start"
  }
}
            </x-docs-code-block>
        </div>

        <div class="bg-blue-950/40 border border-blue-800/40 rounded-lg px-4 py-3 text-sm text-blue-300">
            💡 Ваш сервер должен ответить любым HTTP 2xx в течение <strong>10 секунд</strong>, иначе доставка считается неудачной.
        </div>
    </section>

    {{-- Ошибки --}}
    <section id="errors" class="space-y-6">
        <h2 class="text-2xl font-semibold text-white border-b border-gray-800 pb-3">Коды ошибок</h2>
        <div class="overflow-x-auto">
            <table class="w-full text-sm text-left border-collapse">
                <thead>
                    <tr class="border-b border-gray-800 text-gray-500 uppercase text-xs tracking-wider">
                        <th class="py-2 pr-6">HTTP код</th>
                        <th class="py-2 pr-6">Причина</th>
                        <th class="py-2">Решение</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-800/60 text-gray-300">
                    <tr>
                        <td class="py-3 pr-6"><code class="text-pink-400">400</code></td>
                        <td class="py-3 pr-6">Telegram отклонил запрос метода</td>
                        <td class="py-3">Проверьте JSON: <code class="text-pink-400">error_code</code>, <code class="text-pink-400">description</code> и <code class="text-pink-400">parameters</code>. При Telegram 429 время ожидания находится в <code class="text-pink-400">parameters.retry_after</code></td>
                    </tr>
                    <tr>
                        <td class="py-3 pr-6"><code class="text-pink-400">401</code></td>
                        <td class="py-3 pr-6">Отсутствует или неверный API-ключ</td>
                        <td class="py-3">Проверьте заголовок <code class="text-pink-400">Authorization: Bearer ...</code></td>
                    </tr>
                    <tr>
                        <td class="py-3 pr-6"><code class="text-pink-400">403</code></td>
                        <td class="py-3 pr-6">Бот отключён или метод запрещён</td>
                        <td class="py-3">Включите бота в кабинете; не используйте <code class="text-pink-400">getUpdates</code></td>
                    </tr>
                    <tr>
                        <td class="py-3 pr-6"><code class="text-pink-400">404</code></td>
                        <td class="py-3 pr-6">Бот не найден</td>
                        <td class="py-3">Проверьте <code class="text-pink-400">botPublicId</code> в URL</td>
                    </tr>
                    <tr>
                        <td class="py-3 pr-6"><code class="text-pink-400">429</code></td>
                        <td class="py-3 pr-6">Превышен лимит BotGate</td>
                        <td class="py-3">Учитывайте <code class="text-pink-400">Retry-After</code> и снизьте частоту: базовые лимиты — 600 запросов/мин на API-ключ и 300 на бота</td>
                    </tr>
                    <tr>
                        <td class="py-3 pr-6"><code class="text-pink-400">502</code></td>
                        <td class="py-3 pr-6">Telegram недоступен</td>
                        <td class="py-3">Проверьте доступность и результат операции перед повтором: отправка с неизвестным результатом может создать дубликат</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </section>

    {{-- Поддержка --}}
    <section id="support" class="space-y-6">
        <h2 class="text-2xl font-semibold text-white border-b border-gray-800 pb-3">Техническая поддержка</h2>
        <p class="text-gray-400">
            Если у вас возникли вопросы по работе с API, проблемы с интеграцией или другие технические трудности —
            напишите нам на
            <a href="mailto:support@bot-gate.ru" class="text-indigo-400 hover:text-indigo-300 transition">support@bot-gate.ru</a>.
            Мы постараемся ответить как можно скорее.
        </p>
    </section>

    {{-- Footer --}}
    <footer class="border-t border-gray-800 pt-8 text-center text-sm text-gray-600">
        BotGate &mdash; Proxy для Telegram Bot API &bull; <a href="{{ route('login') }}" class="hover:text-gray-400 transition">Войти</a>
    </footer>

</main>
@include('partials.cookie-consent')
</body>
</html>
