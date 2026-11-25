# BotGate Public API Compatibility Contract

Постоянный контракт совместимости публичного API при изменениях реализации.

Внутренний маршрут
`Laravel → Proxy Node → Telegram` и его дальнейшие изменения не должны требовать изменений у клиентов
BotGate. Это относится ко всему публичному API, а не только к файлам.

BotGate не предоставляет отдельный JSON API для управления аккаунтом,
профилем или пользователями. Web-страницы личного кабинета не нужно ошибочно
включать в Telegram API contract. Bearer API key здесь является технической
защитой Telegram proxy/file endpoints и определяет владельца доступных ботов.

## Источники текущего поведения

- `bot-gate-main/routes/api.php`;
- публичная страница `bot-gate-main/resources/views/docs/index.blade.php`;
- API middleware, controllers и существующие feature tests;
- фактически поддерживаемое поведение production/dev API, подтверждённое
  regression-тестами.

При расхождении документации и фактического поведения сначала зафиксировать
расхождение и принять отдельное решение. Не менять контракт попутно с
внутренними доработками.

## Инварианты публичного API

### Общие

- существующие URL и допустимые HTTP methods;
- Bearer API-key authentication;
- сокрытие существования чужого бота теми же публичными ответами;
- проверка владельца и статуса бота;
- rate limits для API key и отдельного бота, включая HTTP 429;
- запрет `getUpdates`;
- текущие JSON-структуры, HTTP statuses и публичные тексты/коды ошибок;
- отсутствие Telegram bot token и внутренних Proxy Node деталей в ответах;
- webhook routes, проверка webhook secret и формат доставляемых updates.

Входящий `/api/v1/telegram/webhook/{botPublicId}/{secret}` сохраняется при приёме
через отдельный webhook-домен Nginx. Payload, клиентский URL, HMAC и клиентские
заголовки не меняются. Повтор `update_id` одного бота не создаёт вторую доставку;
без `update_id` сохраняется независимый приём. Laravel возвращает 200 только после
атомарного сохранения события и database-job; сбой транзакции возвращает 503,
чтобы Telegram мог повторить update. Неправильный secret/неактивный бот — прежний 403.
Параметры произвольных публичных `setWebhook` API-вызовов клиентов не переписываются.

### Telegram Bot API proxy

- endpoint `/v1/bots/{botPublicId}/{method}`;
- JSON-запросы принимаются и ответы Telegram возвращаются без несовместимого
  преобразования;
- методы Telegram, параметры, Unicode, `null`, boolean, числа, массивы и
  вложенные объекты не должны менять смысл;
- Telegram error response и `retry_after` не должны терять значимые данные;
- внутренний выбор Proxy Node не виден клиенту.

### Multipart upload

- тот же endpoint `/v1/bots/{botPublicId}/{method}`;
- обычный `multipart/form-data` с текстовыми полями и файлами;
- документированный сценарий `sendPhoto` с `chat_id`, `photo`, `caption`;
- исходные имена файлов и Unicode-поля;
- проверить несколько файлов и Telegram `attach://...` до объявления полной
  поддержки этих сценариев.

`sendMediaGroup` с двумя файловыми полями, двумя `attach://...` references,
Unicode caption и исходными filenames/body успешно проходит через публичный
Laravel route и внутренний multipart Proxy transport. Пользователь также
подтвердил успешный реальный dev smoke-test этого сценария.

### Telegram file download

- сначала JSON-вызов `getFile`, затем
  `GET /v1/bots/{botPublicId}/files/{file_path}`;
- бинарный streaming response без загрузки всего файла в память;
- сохранение `Content-Type` и `Content-Length`, когда upstream их передал;
- прежнее поведение для чужого/отключённого бота, отсутствующего файла и
  connection error.

Внутренний streaming endpoint Go Proxy и его вызов через Laravel
`ProxyNodeManager` развёрнуты на dev. Публичный route и зафиксированные
response semantics не изменены; пользователь подтвердил реальный download,
binary body/headers и публичный 404.

## Правило реализации

До изменения соответствующего внутреннего транспорта:

1. добавить regression-тесты через публичный Laravel route;
2. зафиксировать фактическое поведение до изменения;
3. реализовать новый внутренний маршрут;
4. прогнать тот же набор тестов без изменения клиентских ожиданий;
5. выполнить smoke-test документированных примеров;
6. обновлять публичную документацию только при явно согласованном изменении
   API, а не для отражения невидимой внутренней архитектуры.

## Минимальная regression-матрица

- корректная и некорректная API-key проверка Telegram endpoints;
- бот существует / чужой / отключён / отсутствует;
- разрешённый Telegram method и запрещённый `getUpdates`;
- JSON success, Telegram JSON error, invalid upstream response и connection
  error;
- rate-limit response;
- webhook success, неправильный secret и неизменность update payload;
- multipart success и error cases;
- file download success, headers, streaming, 404 и 502.

## Rate limit compatibility status

Оба публичных limiter-а покрыты детерминированными route-level тестами:

- API-key limiter возвращает прежний JSON error и HTTP 429, а также стандартные
  `Retry-After`, `X-RateLimit-Limit`, `X-RateLimit-Remaining`;
- bot limiter сохраняет отдельный публичный error и те же throttle headers;
- счётчики разных Bearer keys и разных bot public IDs независимы;
- cache key API limiter строится по SHA-256 Bearer key: исходный API key не
  хранится, а первый запрос не переключается между IP и user ID из-за порядка
  middleware.
