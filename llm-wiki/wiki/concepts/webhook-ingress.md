---
title: Повторное получение обновления Telegram
type: concept
summary: Когда создаются событие и задание, как BotGate узнаёт повтор и что получит Telegram.
sources: [BG-ARCH, BG-CONTRACT, BG-INTEGRATION, BG-SPEC]
updated: 2026-11-25
---

# Повторное получение обновления Telegram

При включённой доставке Laravel блокирует строку бота, ищет пару bot_id и update_id и сохраняет новое событие, задание очереди webhooks, отметку queued_at и счётчик в одной транзакции. Соединение webhook_ingress пишет в таблицу jobs той же БД до commit. Исключение при записи откатывает транзакцию. [BG-ARCH:76-81 «database-job атомарно»] [code:app/Services/WebhookIngress.php#accept] [code:app/Services/WebhookIngress.php#enqueue] [code:config/queue.php]

Повтор с тем же update_id у того же бота подтверждается без второй доставки. У другого бота тот же номер независим. При отсутствии update_id каждый приём создаёт отдельное событие. Исключение для старых данных: pending с нулём попыток и пустым queued_at восстанавливает пропущенное задание для существующего события. Гарантия действует, пока запись хранится. [BG-ARCH:81-89 «пока событие хранится»] [code:tests/Feature/Api/WebhookIngressTest.php#test_updates_without_id_remain_independent_and_ids_are_scoped_to_bot] [code:tests/Feature/Api/WebhookIngressTest.php#test_legacy_pending_event_is_recovered_once_and_duplicate_jobs_cannot_deliver_twice]

Конкурентный приём проверяют два отдельных процесса с общей SQLite. После повтора в БД остаётся одно событие и одно задание. [code:tests/Feature/Api/WebhookConcurrencyTest.php#test_simultaneous_receivers_commit_only_one_event_and_job] Откуда приходит запрос, описано в [[components]].

После успешной записи контроллер отвечает Telegram HTTP 200. Неверный secret или неактивный бот дают 403. Сбой транзакции даёт 503, поэтому Telegram может повторить запрос. У активного бота с верным secret есть отдельный случай: при выключенной доставке или пустом клиентском URL контроллер тоже возвращает 200, но событие не записывает. [BG-CONTRACT:40-45 «сбой транзакции возвращает 503»] [code:app/Http/Controllers/Api/V1/TelegramWebhookController.php#receive] [code:tests/Feature/Api/WebhookIngressTest.php#test_inactive_deleted_and_delivery_disabled_bots_keep_their_responses] [code:tests/Feature/Api/WebhookIngressTest.php#test_failure_after_job_insert_rolls_back_both_event_and_job]

HTTP 200 на входе подтверждает результат приёма. Собственные попытки отправки клиентского POST, HMAC и повторяющийся Event ID разбираются в [[webhook-delivery]]. [BG-INTEGRATION:146-149 «BotGate saves the event and queues a delivery job»]

**Срок хранения.** Ежедневная команда удаляет обычные события старше 30 дней, failed — старше 60, API-журналы — старше 14. Сравнение идёт с created_at. Дедупликация заканчивается при фактическом удалении события. ТЗ предлагало Redis для очередей в целом. Для первичного приёма текущая архитектура явно требует общую БД, чтобы откат охватывал и событие, и задание. [BG-SPEC:518-526 «API logs хранить 14 дней … 60 дней»] [BG-ARCH:76-80 «ту же основную БД и таблицу jobs»] [code:app/Console/Commands/CleanupLogsCommand.php#handle] [code:routes/console.php]
