# Техническое задание: BotGate

## 1\. Описание проекта

BotGate — веб-сервис-прослойка для работы с Telegram Bot API через зарубежный сервер.

Проблема: Telegram Bot API может нестабильно работать из России без прокси. Пользователи вынуждены использовать платные и ненадежные прокси. BotGate решает это тем, что пользователь обращается к API BotGate, а BotGate уже с зарубежного сервера выполняет запросы к Telegram Bot API.

Домен проекта: bot-gate.ru.

Проект разрабатывается на Laravel. На сервере уже развернут чистый Laravel-проект.

## 2\. Основной принцип работы

Пользователь регистрируется в сервисе, добавляет своего Telegram-бота через bot token, получает Bot ID и использует API BotGate вместо прямого Telegram API.

Пример:

POST https://bot-gate.ru/api/v1/bots/{bot_public_id}/telegram/sendMessage  
Authorization: Bearer {api_key}  
Content-Type: application/json

Тело запроса:

{  
"chat_id": 123456,  
"text": "Привет"  
}

BotGate внутри делает запрос:

POST https://api.telegram.org/bot&lt;TOKEN&gt;/sendMessage

И возвращает пользователю ответ Telegram без постановки в очередь.

## 3\. Технологии

Backend:

- Laravel, актуальная версия;
- PHP 8.4;
- MySQL или MariaDB;
- Redis для очередей и rate limit;
- Laravel Queue;
- Laravel Scheduler;
- Laravel Sanctum или собственная API-key авторизация;
- Laravel Mail для подтверждения email.

Frontend:

- можно использовать стандартный Laravel Blade;
- либо Vue, если уже предусмотрено в проекте;
- для MVP допустим простой Blade-интерфейс.

Сервер:

- Ubuntu;
- Nginx;
- PHP-FPM;
- SSL через Let’s Encrypt.

## 4\. Роли пользователей

### 4.1. Обычный пользователь

Может:

- зарегистрироваться;
- подтвердить email;
- войти в личный кабинет;
- восстановить пароль;
- получить API key;
- перевыпустить API key;
- добавить Telegram-бота;
- редактировать бота;
- удалить или отключить бота;
- настроить webhook URL для бота;
- видеть статус webhook;
- видеть Bot ID;
- видеть базовую статистику по своим ботам.

### 4.2. Администратор

Может:

- видеть список пользователей;
- видеть список ботов;
- отключать пользователей;
- отключать ботов;
- смотреть внутренние технические логи;
- смотреть ошибки webhook-доставки;
- смотреть статистику запросов.

## 5\. Регистрация и авторизация

### 5.1. Регистрация

Поля:

- email;
- password;
- password confirmation.

После регистрации пользователь должен подтвердить email.

Без подтверждения email пользователь не должен иметь возможность пользоваться API и добавлять ботов.

### 5.2. Вход

Вход по email и паролю.

### 5.3. Восстановление пароля

Стандартное восстановление пароля через email.

### 5.4. API key

У каждого пользователя один API key.

API key нужен для обращения к API BotGate.

Требования:

- создается автоматически после регистрации или после подтверждения email;
- хранится в базе в хешированном виде;
- пользователю показывается только при создании/перевыпуске;
- в личном кабинете показывать маску ключа, например bg_live_\*\*\*\*X7kP;
- пользователь может перевыпустить API key;
- после перевыпуска старый ключ перестает работать.

Формат ключа:

bg_live_xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx

## 6\. Боты

### 6.1. Добавление бота

Пользователь добавляет бота в личном кабинете.

Поля:

- название бота;
- Telegram Bot Token;
- webhook URL, необязательно.

При добавлении нужно выполнить проверку токена через Telegram метод getMe.

Если токен валидный, сохранить данные бота.

Если токен невалидный, показать ошибку.

### 6.2. Хранение токена

Telegram Bot Token должен храниться в базе зашифрованным.

Использовать Laravel encryption или отдельный механизм шифрования.

Токен нужен в расшифрованном виде только во время выполнения запроса к Telegram API.

В личном кабинете токен полностью не показывать.

Показывать только маску:

123456789:AAH\*\*\*\*\*\*\*\*\*\*\*\*\*\*\*

### 6.3. Bot Public ID

У каждого бота должен быть внутренний id и публичный bot_public_id.

В API используется только bot_public_id.

Формат:

bot_8Kx91aPqZ2

Требования:

- уникальный;
- короткий;
- не автоинкрементный;
- с префиксом bot_;
- длина после префикса примерно 10-16 символов.

### 6.4. Один аккаунт — много ботов

Один пользователь может добавить несколько ботов.

Один Telegram Bot Token нельзя добавить повторно в разные аккаунты.

Для проверки уникальности можно хранить дополнительный token_hash.

### 6.5. Статусы бота

Статусы:

active  
disabled  
deleted

deleted можно реализовать через soft delete.

## 7\. Личный кабинет

### 7.1. Главная страница ЛК

Показывать:

- email пользователя;
- API key, замаскированный;
- кнопку перевыпуска API key;
- список ботов;
- кнопку добавления бота.

### 7.2. Список ботов

Для каждого бота показывать:

- название;
- Bot ID;
- username Telegram-бота;
- статус;
- webhook URL;
- webhook статус;
- дата создания;
- количество запросов сегодня;
- количество ошибок сегодня.

### 7.3. Карточка бота

Показывать:

- название;
- Bot Public ID;
- Telegram username;
- Telegram first name;
- статус;
- webhook URL;
- webhook включен/выключен;
- последняя webhook-доставка;
- последняя webhook-ошибка;
- счетчики запросов.

Действия:

- изменить название;
- изменить token;
- изменить webhook URL;
- включить/выключить webhook;
- отключить бота;
- удалить бота.

## 8\. API BotGate

### 8.1. Авторизация API

Все API-запросы выполняются с заголовком:

Authorization: Bearer {api_key}

Если ключ неверный:

{  
"ok": false,  
"error": "Unauthorized"  
}

HTTP status: 401.

### 8.2. Основной endpoint

POST /api/v1/bots/{bot_public_id}/telegram/{method}

Пример:

POST /api/v1/bots/bot_8Kx91aPqZ2/telegram/sendMessage

Тело запроса должно передаваться дальше в Telegram почти без изменений.

### 8.3. Ответ API

BotGate должен возвращать пользователю ответ Telegram API максимально близко к оригинальному.

Если Telegram ответил:

{  
"ok": true,  
"result": {  
"message_id": 123  
}  
}

BotGate возвращает то же самое.

Если Telegram вернул ошибку, BotGate возвращает ошибку Telegram.

### 8.4. Ошибки BotGate

Если ошибка произошла на стороне BotGate, возвращать единый формат:

{  
"ok": false,  
"error": "BotGate error message"  
}

Примеры ошибок:

- неверный API key;
- бот не найден;
- бот отключен;
- превышен лимит;
- метод запрещен;
- ошибка соединения с Telegram;
- некорректный Content-Type.

### 8.5. Поддержка методов Telegram

На MVP не нужно вручную реализовывать каждый метод.

Нужно сделать универсальное проксирование:

{method} из URL → https://api.telegram.org/bot&lt;TOKEN&gt;/{method}

Примеры методов:

- sendMessage;
- sendPhoto;
- sendDocument;
- sendVideo;
- editMessageText;
- deleteMessage;
- answerCallbackQuery;
- setWebhook;
- deleteWebhook;
- getWebhookInfo.

### 8.6. Запрещенные методы

Метод getUpdates в MVP не поддерживать.

При вызове:

POST /api/v1/bots/{bot_public_id}/telegram/getUpdates

Возвращать:

{  
"ok": false,  
"error": "Method getUpdates is not supported"  
}

## 9\. Файлы

Нужно поддержать отправку файлов в Telegram.

### 9.1. JSON с URL файла

Обязательно для MVP.

Пример:

{  
"chat_id": 123456,  
"photo": "https://example.com/image.jpg",  
"caption": "Фото"  
}

### 9.2. Multipart upload

Желательно реализовать в MVP.

BotGate должен принимать multipart-запрос и пересылать его в Telegram как multipart.

Пример endpoint:

POST /api/v1/bots/{bot_public_id}/telegram/sendPhoto  
Authorization: Bearer {api_key}  
Content-Type: multipart/form-data

Поля:

chat_id  
photo  
caption

Файл передается в поле photo.

## 10\. Webhook

### 10.1. Общая схема

Telegram отправляет update на BotGate.

BotGate принимает update, сохраняет событие, ставит задачу в очередь.

Очередь отправляет update на webhook URL пользователя.

Схема:

Telegram → BotGate → Queue → Webhook клиента

### 10.2. Настройка webhook

У каждого бота может быть только один webhook URL.

Пользователь указывает webhook URL в личном кабинете.

После сохранения webhook URL BotGate должен вызвать Telegram метод setWebhook.

Но в Telegram нужно установить не URL пользователя, а URL BotGate:

https://bot-gate.ru/api/v1/telegram/webhook/{bot_public_id}/{secret}

### 10.3. Secret для webhook endpoint

Для каждого бота нужно создать webhook secret.

Формат:

whsec_xxxxxxxxxxxxxxxxxxxxx

Он используется в URL, на который Telegram отправляет updates.

### 10.4. Endpoint приема webhook от Telegram

POST /api/v1/telegram/webhook/{bot_public_id}/{secret}

Логика:

1.  Найти бота по bot_public_id.
2.  Проверить secret.
3.  Проверить, что бот active.
4.  Сохранить входящий update.
5.  Поставить задачу в очередь на доставку клиенту.
6.  Быстро вернуть Telegram ответ 200 OK.

Ответ:

{  
"ok": true  
}

### 10.5. Доставка webhook пользователю

BotGate должен отправить POST-запрос на webhook URL пользователя.

Тело запроса — оригинальный update от Telegram.

Headers:

Content-Type: application/json  
User-Agent: BotGate Webhook  
X-BotGate-Bot-Id: {bot_public_id}  
X-BotGate-Event-Id: {event_id}

Желательно добавить подпись:

X-BotGate-Signature: {hmac_signature}

Подпись можно считать как HMAC SHA256 от body с использованием webhook secret.

### 10.6. Retry доставки

Если webhook пользователя вернул не 2xx или произошла ошибка соединения, выполнить повторные попытки.

Количество попыток: 3.

Пример задержек:

1-я попытка: сразу  
2-я попытка: через 1 минуту  
3-я попытка: через 5 минут

После 3 неудачных попыток статус события становится failed.

### 10.7. Статусы webhook events

Статусы:

pending  
processing  
sent  
failed

Хранить:

- bot_id;
- update_id;
- request body;
- webhook URL;
- status;
- attempts;
- last_attempt_at;
- delivered_at;
- last_error;
- HTTP status последней попытки.

## 11\. Логи

### 11.1. Внутренние API-логи

Логировать для внутренней отладки:

- user_id;
- bot_id;
- method;
- request type: json/multipart;
- HTTP status Telegram;
- Telegram ok true/false;
- error text;
- duration ms;
- created_at.

Тела запросов и ответов можно хранить ограниченно, но не показывать пользователю в ЛК.

### 11.2. Ограничение хранения логов

Добавить команду очистки старых логов.

Например:

- API logs хранить 14 дней;
- webhook events хранить 30 дней;
- failed webhook events можно хранить 60 дней.

Команду повесить на Laravel Scheduler.

## 12\. Rate limits

Даже если сервис бесплатный, технически нужно заложить лимиты.

Начальные значения:

На API key: 600 запросов в минуту  
На одного бота: 300 запросов в минуту  
Webhook deliveries: 1000 событий в минуту на бота

Если лимит превышен:

HTTP status: 429.

Ответ:

{  
"ok": false,  
"error": "Rate limit exceeded"  
}

Лимиты должны быть вынесены в config.

## 13\. Админка

Можно реализовать простую админку внутри Laravel.

### 13.1. Пользователи

Страница списка пользователей:

- ID;
- email;
- email verified;
- статус;
- количество ботов;
- дата регистрации.

Действия:

- отключить пользователя;
- включить пользователя;
- открыть карточку пользователя.

### 13.2. Боты

Страница списка ботов:

- ID;
- Bot Public ID;
- владелец;
- название;
- Telegram username;
- статус;
- webhook включен/выключен;
- дата создания.

Действия:

- отключить бота;
- включить бота;
- открыть карточку бота.

### 13.3. Логи и ошибки

Страницы:

- последние API ошибки;
- последние webhook ошибки;
- failed webhook events;
- общая статистика по запросам.

## 14\. База данных

### 14.1. users

Стандартная таблица Laravel users.

Дополнительные поля:

status enum: active, disabled  
api_key_hash string nullable  
api_key_preview string nullable  
is_admin boolean default false

### 14.2. bots

Поля:

id  
user_id  
public_id  
name  
telegram_token_encrypted  
telegram_token_hash  
telegram_bot_id  
telegram_username  
telegram_first_name  
webhook_url nullable  
webhook_secret  
webhook_enabled boolean  
status enum: active, disabled  
last_webhook_delivery_at nullable  
last_webhook_error text nullable  
created_at  
updated_at  
deleted_at

### 14.3. api_request_logs

Поля:

id  
user_id  
bot_id  
method  
content_type  
telegram_http_status nullable  
telegram_ok boolean nullable  
error text nullable  
duration_ms integer nullable  
ip_address  
created_at

### 14.4. webhook_events

Поля:

id  
bot_id  
telegram_update_id nullable  
payload json  
target_url  
status enum: pending, processing, sent, failed  
attempts integer default 0  
last_http_status nullable  
last_error text nullable  
last_attempt_at nullable  
delivered_at nullable  
created_at  
updated_at

### 14.5. bot_daily_stats

Поля:

id  
bot_id  
date  
api_requests_count  
api_errors_count  
webhook_received_count  
webhook_sent_count  
webhook_failed_count  
created_at  
updated_at

Уникальный индекс:

bot_id + date

## 15\. Очереди

Использовать Laravel Queue.

Очереди:

webhooks  
default

Задачи:

### 15.1. DeliverWebhookEventJob

Назначение:

- взять webhook event;
- отправить POST на webhook URL пользователя;
- обновить статус;
- при ошибке повторить;
- после 3 ошибок поставить failed.

### 15.2. CleanupLogsJob или artisan command

Назначение:

- удаление старых логов;
- удаление старых webhook events.

## 16\. Команды Artisan

Нужно добавить команды:

### 16.1. Очистка логов

php artisan botgate:cleanup-logs

### 16.2. Проверка бота

php artisan botgate:check-bot {bot_public_id}

Команда проверяет токен через Telegram getMe.

### 16.3. Тест webhook доставки

php artisan botgate:test-webhook {bot_public_id}

Отправляет тестовое событие на webhook URL пользователя.

## 17\. Безопасность

Обязательные требования:

- Telegram token хранить зашифрованным;
- API key хранить только в хешированном виде;
- не показывать полный token в ЛК;
- не показывать полный API key после создания;
- проверять принадлежность бота пользователю;
- не использовать внутренний ID бота в публичном API;
- endpoint Telegram webhook защищать secret-частью URL;
- добавить rate limits;
- отключенный пользователь не может пользоваться API;
- отключенный бот не может использоваться через API;
- использовать HTTPS;
- валидировать webhook URL;
- webhook URL должен быть только https://;
- запретить локальные/private IP в webhook URL, чтобы избежать SSRF.

Запретить webhook URL на:

localhost  
127.0.0.1  
0.0.0.0  
10.0.0.0/8  
172.16.0.0/12  
192.168.0.0/16  
::1

## 18\. Основные страницы

### 18.1. Публичная часть

Страницы:

- главная;
- как работает сервис;
- документация API;
- контакты;
- политика конфиденциальности;
- пользовательское соглашение.

### 18.2. Личный кабинет

Страницы:

- вход;
- регистрация;
- подтверждение email;
- восстановление пароля;
- dashboard;
- список ботов;
- добавление бота;
- редактирование бота;
- карточка бота;
- настройки API key.

### 18.3. Админка

Страницы:

- dashboard;
- пользователи;
- карточка пользователя;
- боты;
- карточка бота;
- API ошибки;
- webhook ошибки;
- статистика.

## 19\. Документация API

Нужно сделать страницу документации.

Минимально описать:

### 19.1. Авторизация

Authorization: Bearer {api_key}

### 19.2. Отправка сообщения

POST /api/v1/bots/{bot_public_id}/telegram/sendMessage

{  
"chat_id": 123456,  
"text": "Привет"  
}

### 19.3. Отправка фото по URL

POST /api/v1/bots/{bot_public_id}/telegram/sendPhoto

{  
"chat_id": 123456,  
"photo": "https://example.com/photo.jpg",  
"caption": "Фото"  
}

### 19.4. Webhook

Объяснить, что пользователь указывает свой webhook URL в личном кабинете, а BotGate сам настраивает Telegram webhook на свой endpoint.

## 20\. Порядок разработки

### Этап 1. Базовая инфраструктура Laravel

- настроить .env;
- подключить БД;
- настроить mail;
- настроить Redis;
- настроить очереди;
- настроить scheduler;
- настроить авторизацию пользователей.

### Этап 2. Пользователи и API key

- регистрация;
- email verification;
- восстановление пароля;
- генерация API key;
- перевыпуск API key;
- middleware для API key.

### Этап 3. Боты

- миграция bots;
- добавление бота;
- шифрование token;
- проверка token через Telegram getMe;
- генерация public_id;
- список ботов;
- редактирование;
- отключение;
- удаление.

### Этап 4. Telegram API proxy

- endpoint /api/v1/bots/{bot_public_id}/telegram/{method};
- проверка API key;
- проверка прав на бота;
- запрет getUpdates;
- отправка JSON в Telegram;
- возврат ответа Telegram;
- логирование;
- rate limit.

### Этап 5. Файлы

- поддержка JSON с URL файлов;
- поддержка multipart-запросов;
- прокидывание multipart в Telegram.

### Этап 6. Webhook

- настройка webhook URL в ЛК;
- генерация webhook secret;
- вызов Telegram setWebhook;
- endpoint приема updates от Telegram;
- сохранение webhook events;
- очередь доставки;
- retry 3 попытки;
- статусы доставки;
- отображение статуса в ЛК.

### Этап 7. Админка

- список пользователей;
- список ботов;
- отключение пользователя;
- отключение бота;
- просмотр ошибок;
- просмотр статистики.

### Этап 8. Документация и финальная проверка

- страница API-документации;
- тесты основных методов;
- тесты webhook;
- проверка rate limits;
- проверка безопасности webhook URL;
- проверка очистки логов.

## 21\. Критерии готовности MVP

MVP считается готовым, если:

1.  Пользователь может зарегистрироваться и подтвердить email.
2.  Пользователь может получить API key.
3.  Пользователь может добавить Telegram-бота по token.
4.  BotGate проверяет token через getMe.
5.  Пользователь может отправить сообщение через BotGate API.
6.  Ответ Telegram возвращается пользователю.
7.  Пользователь может настроить webhook URL.
8.  Telegram отправляет update на BotGate.
9.  BotGate доставляет update на webhook пользователя.
10. При ошибке доставки выполняется 3 попытки.
11. В ЛК виден webhook статус.
12. Администратор может видеть пользователей и ботов.
13. Telegram token хранится зашифрованным.
14. API key хранится в хешированном виде.
15. Есть базовые rate limits.
16. Есть технические логи.
17. getUpdates запрещен.
18. Работает отправка файлов хотя бы через URL.
19. Работает очистка старых логов.
20. Есть базовая API-документация.

## 22\. Что не входит в MVP

Не реализовывать на первом этапе:

- биллинг;
- тарифы;
- публичную оплату;
- сложную антиспам-систему;
- polling через getUpdates;
- несколько webhook URL на одного бота;
- публичные логи запросов в личном кабинете;
- сложную аналитику;
- командный доступ нескольким пользователям к одному аккаунту;
- OAuth-входы;
- Telegram Mini App;
- мобильное приложение.

## 23\. Рабочее название

Название сервиса: BotGate.

Домен: bot-gate.ru.

В интерфейсе использовать написание:

BotGate