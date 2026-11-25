---
title: Доставка webhook на сервер клиента
type: concept
summary: Как проверить подпись и почему один Event ID может прийти несколько раз.
sources: [BG-ARCH, BG-INTEGRATION]
updated: 2026-11-25
---

# Доставка webhook на сервер клиента

После [[webhook-ingress]] рабочий процесс отправляет POST на сохранённый адрес клиента. X-BotGate-Event-Id содержит ID события BotGate. Подпись X-BotGate-Signature считается по фактически отправленным байтам JSON с секретом бота. Получателю следует проверять подпись до разбора тела. [BG-INTEGRATION:156-169 «HMAC-SHA256 of the raw request body»] [code:app/Jobs/DeliverWebhookEventJob.php#handle] [code:tests/Feature/Jobs/DeliverWebhookEventJobTest.php#test_successful_delivery_preserves_payload_and_adds_hmac_headers]

Ожидается HTTP 2xx за 10 секунд. Первая попытка выполняется из очереди сразу, после первой ошибки следующая назначается через 60 секунд, после второй — через 300. Третья ошибка завершает событие со статусом failed. Если клиент уже выполнил действие, но ответ потерялся, следующий POST придёт с тем же Event ID. Для действий вроде создания заказа клиенту нужен свой учёт обработанных событий. [BG-INTEGRATION:146-149 «up to 3 attempts with delays»] [BG-INTEGRATION:186 «any HTTP 2xx within … 10 seconds»] [BG-ARCH:87-89 «с тем же … X-BotGate-Event-Id»] [code:tests/Feature/Jobs/DeliverWebhookEventJobTest.php#test_third_failure_marks_the_event_failed_without_another_retry]
