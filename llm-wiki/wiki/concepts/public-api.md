---
title: Публичный API и совместимость
type: concept
summary: Как вызвать метод Telegram и что клиент вправе ожидать при замене внутреннего транспорта.
sources: [BG-AGENT-CONTRACT, BG-CONTRACT, BG-INTEGRATION, BG-SPEC]
updated: 2026-11-25
---

# Публичный API и совместимость

При изменении транспорта исходные ожидания клиента фиксируют через публичные Laravel-маршруты. Сверяют URL, доступ, лимиты, форматы ответа, webhook и поток файлов. Решение об изменении контракта принимает владелец продукта. [BG-AGENT-CONTRACT:9-15 «Дополнять regression-тесты публичных маршрутов перед переключением поведения»]

Для проверки возьмите [code:routes/api.php], реализацию [code:app/Http/Controllers/Api/V1/TelegramProxyController.php#handle] и маршрутный тест [code:tests/Feature/Api/PublicTelegramApiCompatibilityTest.php#test_json_proxy_preserves_the_telegram_response]. Внутренняя граница сервиса описана в [[components]].

Клиент использует Bearer-ключ владельца и публичный ID бота. При вызове разрешённого метода чужой и отсутствующий бот получают одинаковый 404. Отключённый собственный бот и запрещённый getUpdates дают 403. Неверный ключ даёт 401. Вызов Telegram с ok=false сохраняет тело ошибки, включая retry_after, если Telegram его прислал, и возвращается клиенту с HTTP 400. Локальный сбой соединения даёт 502. [BG-CONTRACT:28-38 «сокрытие существования чужого бота теми же публичными ответами»] [BG-CONTRACT:50-56 «Telegram error response и retry_after не должны терять значимые данные»] [code:app/Http/Middleware/AuthenticateApiKey.php#handle] [code:tests/Feature/Api/PublicTelegramApiCompatibilityTest.php#test_telegram_error_body_is_returned_with_the_existing_public_status]

Лимиты ключа и бота имеют отдельные счётчики. При исчерпании одного клиент получает HTTP 429 и заголовки ограничения, включая Retry-After. [BG-CONTRACT:112-119 «счётчики разных Bearer keys и разных bot public IDs независимы»] [code:app/Providers/AppServiceProvider.php#configureRateLimiting] [code:tests/Feature/Api/PublicRateLimitCompatibilityTest.php#test_bot_limiter_returns_the_public_429_and_does_not_block_another_bot]. Передача файлов описана в [[telegram-files]].

Документированный запрос клиента — POST /api/v1/bots/{botPublicId}/{method}. Для sendMessage достаточно JSON и Bearer-ключа. Значения по умолчанию составляют 600 запросов в минуту на ключ и 300 на бота. В конкретном окружении они задаются конфигурацией. [BG-INTEGRATION:30-38 «POST https://bot-gate.ru/api/v1/bots/{botPublicId}/{method}»] [BG-INTEGRATION:82-87 «600 requests/min … 300 requests/min»] [code:config/botgate.php] [code:routes/api.php]

**История адреса.** В MVP был предложен /api/v1/bots/{bot_public_id}/telegram/{method}. В текущем маршруте, контракте и инструкции сегмент /telegram/ отсутствует. Для новой интеграции используйте адрес из раздела выше. Дата перехода [нет в источниках]. [BG-SPEC:270-276 «/telegram/{method}»] [BG-CONTRACT:48-52 «/v1/bots/{botPublicId}/{method}»] [code:routes/api.php] См. [[bot-account]].

> [!question] Владельцу продукта, 25.11.2026. Остаётся ли актуальным предложенный в ТЗ предел 1000 доставок в минуту на бота? Конфигурация и маршрутные тесты подтверждают лимиты ключа и бота для клиентского API. В обработчике доставки такой прикладной лимит не найден. [BG-SPEC:532-536 «1000 событий в минуту на бота»] [BG-CONTRACT:112-117 «Оба публичных limiter-а»] [code:config/botgate.php] [code:app/Jobs/DeliverWebhookEventJob.php#handle]
