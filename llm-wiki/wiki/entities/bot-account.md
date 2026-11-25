---
title: Аккаунт, ключ и зарегистрированный бот
type: entity
summary: Какие идентификаторы нужны пользователю и как получить ключ в кабинете.
sources: [BG-INTEGRATION, BG-SPEC]
updated: 2026-11-25
---

# Аккаунт, ключ и зарегистрированный бот

У владельца один действующий API-ключ для его ботов. В разделе /dashboard/api-key подтверждённый пользователь запрашивает ключ. Laravel сохраняет SHA-256 и маску, а полный ключ один раз показывает после выпуска. При перевыпуске хеш заменяется и старый ключ перестаёт находить владельца. [BG-SPEC:118-129 «после перевыпуска старый ключ перестает работать»] [code:app/Services/ApiKeyService.php#issueForUser] [code:app/Http/Controllers/DashboardController.php#reissueApiKey] [code:tests/Feature/DashboardJournalsTest.php#test_api_key_is_moved_and_new_key_is_shown_only_once]

При добавлении бота сервис проверяет его Telegram-токен через getMe. Бот получает public_id, связанный с владельцем. Токен хранится с encrypted cast, для проверки повторной регистрации сервис ищет хеш. Для вызова [[public-api]] нужны публичный ID и ключ аккаунта. [BG-SPEC:145-155 «проверку токена через Telegram метод getMe»] [BG-INTEGRATION:8-12 «Copy your Bot Public ID»] [code:app/Services/BotService.php#create] [code:app/Models/Bot.php#casts] [code:tests/Feature/AnalyticsGoalsTest.php#test_successful_bot_creation_emits_a_goal_without_exposing_bot_identifiers]

> [!question] Владельцу продукта, 25.11.2026. Как закрепить порядок первичной выдачи ключа в клиентской инструкции? ТЗ предусматривает автоматический выпуск, руководство предлагает скопировать ключ, а протестированный путь новой учётной записи начинается с кнопки «Создать ключ» в кабинете. [BG-SPEC:122-129 «создается автоматически после регистрации или после подтверждения email»] [BG-INTEGRATION:8-11 «Copy your API key»] [code:tests/Feature/DashboardJournalsTest.php#test_api_key_is_moved_and_new_key_is_shown_only_once]
