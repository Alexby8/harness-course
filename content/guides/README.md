# Рабочий каталог руководств

Источник сайта — таблица `guides`. Здесь хранится рабочая копия для подготовки
статей агентом, а не второй источник данных для deployment.

- `current.json`: подтверждённый каталог, включая скрытые записи. Начальная
  копия получена из пользовательского экспорта dev 7 октября 2026 года,
  `storage/app/guides-current.json` (14 статей). Совпадение с production принято
  по условию пользователя, отдельная сверка двух серверов не выполнялась.
  Каталог дополнен применёнными пакетами после пользовательского подтверждения
  production-импортов 7 октября 2026 года; всего 18 статей. Последние добавленные
  статьи — `telegram-bot-not-reading-group-messages` и
  `telegram-bot-receive-and-save-files`; их публичные страницы, canonical/OG
  и карточки каталога проверены после импорта.
- `packages/<slug>.json`: временные пакеты для импорта. Само наличие пакета
  не означает, что он опубликован или применён хотя бы на dev. После завершения
  переноса пакеты удаляются; папка может отсутствовать, если их нет.

Подготовлены 66 пакетов новых статей. Завершение их переноса на production пока не подтверждено.
Отдельный `packages/telegram-bot-botfather.json` содержит иллюстрированную редакцию
существующей статьи из `current.json`; её импорт также не подтверждён.

- `packages/telegram-link-website-account.json` — привязка Telegram к аккаунту сайта;
- `packages/telegram-bot-publish-to-channel.json` — публикация и редактирование постов канала;
- `packages/telegram-bot-send-photo-album.json` — отправка фотоальбомов;
- `packages/telegram-bot-long-messages.json` — разбиение длинного текста;
- `packages/telegram-bot-step-by-step-form.json` — пошаговая анкета на PHP;
- `packages/telegram-bot-request-phone-number.json` — запрос и проверка телефона;
- `packages/telegram-bot-check-channel-subscription.json` — проверка подписки на канал;
- `packages/laravel-telegram-broadcast.json` — рассылка через очередь Laravel;
- `packages/telegram-bot-support-operator-group.json` — поддержка с ответами операторов из группы;
- `packages/laravel-telegram-appointment-reminders.json` — напоминания о записи через планировщик Laravel;
- `packages/telegram-bot-google-sheets-leads.json` — сохранение заявок в Google Таблицы;
- `packages/telegram-bot-product-catalog-pagination.json` — каталог с постраничной навигацией;
- `packages/telegram-bot-location-nearest-branch.json` — геолокация и выбор ближайшего филиала;
- `packages/telegram-bot-start-source-tracking.json` — источники запусков и заявок через deep linking;
- `packages/telegram-bot-receive-photo-album.json` — приём фотоальбома и привязка к заявке;
- `packages/telegram-bot-polls-and-quizzes.json` — опросы, викторины и обработка ответов;
- `packages/telegram-bot-join-requests.json` — заявки на вступление, проверка доступа и решение администратора;
- `packages/telegram-bot-command-scopes.json` — разные меню команд по ролям и языкам;
- `packages/telegram-mini-app-or-chatbot.json` — выбор между Mini App и чат-ботом для бизнеса;
- `packages/telegram-mini-app-html-php-form.json` — первое Mini App с HTML-формой, проверкой initData и сохранением на PHP;
- `packages/telegram-mini-app-initdata-auth-php-laravel.json` — проверка initData, сессии и доступ к заявкам на PHP и Laravel;
- `packages/woocommerce-telegram-order-notifications.json` — уведомления о заказах WooCommerce с проверкой webhook;
- `packages/n8n-telegram-botgate-http-request.json` — отправка уведомлений из n8n через HTTP Request и BotGate;
- `packages/telegram-inline-bot-product-search.json` — inline-поиск публичных товаров и отправка карточек;
- `packages/telegram-mini-app-white-screen.json` — диагностика запуска Mini App и белого экрана;
- `packages/telegram-bot-monitoring.json` — мониторинг webhook, очередей, отправки и доставки тревог;
- `packages/telegram-bot-token-leak.json` — отзыв утёкшего токена и восстановление подключений;
- `packages/telegram-bot-testing-without-client-messages.json` — тестирование бота без отправки сообщений клиентам;
- `packages/telegram-mini-app-cart-checkout.json` — корзина, проверка цены и остатка, повторное оформление заказа;
- `packages/telegram-mini-app-iphone-android.json` — высота окна, клавиатура, темы и безопасные отступы Mini App;
- `packages/telegram-mini-app-product-order-links.json` — прямые ссылки на товары и заказы с проверкой владельца;
- `packages/telegram-bot-appointment-booking.json` — расписание, временный резерв, подтверждение и отмена записи;
- `packages/telegram-bot-bitrix24-leads.json` — передача заявок в CRM, связь с лидом и восстановление после сбоя;
- `packages/laravel-telegram-pdf-document.json` — Blade-шаблон PDF, кириллица и отправка документа из очереди;
- `packages/telegram-bot-voice-to-text.json` — короткие и длинные голосовые сообщения, распознавание и правка расшифровки;
- `packages/telegram-bot-request-approval.json` — согласование закупок, комментарии, права и история решений;
- `packages/telegram-bot-back-in-stock-notifications.json` — подписка на наличие товара, версии остатков, отмена и отправка уведомления;
- `packages/telegram-stars-digital-access.json` — оплата цифрового доступа в Stars, подтверждение платежа, выдача права и возврат;
- `packages/telegram-bot-qr-tickets.json` — персональные QR-билеты, экран проверяющего, права сотрудников и однократный проход;
- `packages/telegram-bot-referral-program.json` — реферальные ссылки, привязка нового клиента, начисление и отмена бонуса;
- `packages/telegram-mini-app-notification-settings.json` — категории уведомлений, часовые пояса, часы тишины и разрешение писать;
- `packages/telegram-mini-app-share-message.json` — подготовка публичной карточки, shareMessage, отмена и защита личных данных;
- `packages/laravel-telegram-login-oidc.json` — вход через OIDC, проверка токенов, создание аккаунта и безопасная привязка;
- `packages/telegram-business-bot-customer-replies.json` — бизнес-подключение, права бота, ответы клиентам и передача человеку;
- `packages/telegram-bot-select-notification-chat.json` — выбор рабочего чата, проверка прав, пробная отправка и отключение;
- `packages/telegram-bot-group-moderation.json` — запрещённые ссылки, предупреждения, временные ограничения и отмена решений;
- `packages/github-telegram-notifications-botgate.json` — события GitHub, подпись webhook, фильтры и очередь уведомлений через BotGate;
- `packages/telegram-html-widgets-order-card.json` — HTML-карточка заказа, проверка владельца, нажатия и обновление состояния Telegram Widgets.
- `packages/telegram-bot-company-knowledge-base.json` — поиск по FAQ, ответы с источниками, отказ при недостатке данных и передача оператору;
- `packages/telegram-channel-paid-subscription-stars.json` — подписные ссылки канала, продление в Stars, проверка доступа и обращения подписчиков;
- `packages/telegram-bot-google-calendar.json` — создание, перенос и отмена событий, OAuth, часовые пояса и синхронизация изменений;
- `packages/telegram-mini-app-form-draft.json` — автосохранение и восстановление черновика на PHP, CloudStorage как альтернатива, очистка и конфликты между устройствами;
- `packages/telegram-mini-app-file-upload.json` — вложения к заявке через Mini App, FormData и PHP, проверка файлов и закрытое хранение;
- `packages/telegram-daily-sales-report.json` — ежедневная сводка, границы периодов, полнота исходных данных, расписание и доставка;
- `packages/telegram-bot-customer-reviews.json` — оценки по заказам, комментарии, низкая оценка и отдельное разрешение на публикацию;
- `packages/laravel-multiple-telegram-bots.json` — настройки, webhook, разделение данных и очередей нескольких ботов в Laravel;
- `packages/telegram-bot-multiple-languages.json` — перевод диалога в Laravel, сохранение языка, ошибки, кнопки, уведомления и запасной перевод;
- `packages/telegram-bot-mini-course.json` — курс из пяти занятий, проверка заданий, сохранение прогресса, открытие уроков и отключение напоминаний;
- `packages/telegram-mini-app-service-calculator.json` — серверный расчёт стоимости, сохранение предложения и заявка менеджеру;
- `packages/telegram-mini-app-loyalty-card.json` — бонусная карта, журнал начислений и списаний, сроки и полные возвраты;
- `packages/telegram-bot-waiting-list.json` — очередь конкретного времени, предложение с ограниченным сроком и передача следующему клиенту;
- `packages/telegram-bot-employee-tasks.json` — задачи сотрудников, роли, версии карточек, просрочка и история действий.

- `packages/telegram-bot-physical-goods-payments.json` — физические товары, платёжный провайдер, доставка, резерв и сверка оплаты;
- `packages/telegram-bot-google-sheets-catalog.json` — управление каталогом из Google Таблиц, проверка данных и последняя рабочая версия;
- `packages/telegram-rss-channel-autopost.json` — RSS-анонсы в канале, первая загрузка, идентификаторы и расписание публикаций;
- `packages/telegram-bot-ai-streaming-replies.json` — поток ИИ, временный черновик Telegram, остановка и постоянный финальный ответ.

Эти статьи ещё не входят в подтверждённый `current.json`.

Рабочий формат соответствует `Guide::FIELDS`: поля `is_featured`, `related_order`
и `related_title` удалены вместе с выделенной карточкой и блоком других руководств.
Из рабочей копии убраны только эти поля; содержание и порядок статей сохранены.
Старые экспорты с этими полями принимаются импортом, но поля игнорируются.
Каталог сайта показывает по 12 обычных карточек в порядке `sort_order`, затем `id`.

## Порядок работы

### Иллюстрации

Блок `guide-image` хранит `src`, `alt`, необязательную `caption`, размеры `width`
и `height` в пикселях. Допустимы только локальные пути вида
`/images/guides/slug-stati/illustration.jpg` (PNG, JPG, WebP, AVIF).
Файлы находятся в `public/images/guides/` и выкладываются вместе с кодом.
Внешние URL, SVG, data URL и переходы между каталогами не принимаются.

Для отдельной вертикальной версии указываются все три поля: `mobile_src`,
`mobile_width`, `mobile_height`. На экранах до 640 px она заменяет основной
рисунок. Обе версии имеют размеры для резервирования места и отображаются
без ссылки и увеличения по нажатию. Подпись и alt — обычный текст, без HTML.
В админке блок доступен как «Иллюстрация»; загрузки файлов через редактор нет.

Для статьи о Widgets исходные макеты собираются из её HTML-примера командой
`node content/guides/illustrations/build-widget-illustrations.mjs OUTPUT_DIRECTORY`.
Если пакет уже перенесён в `current.json`, сборщик использует статью из каталога.
Полученные HTML служат локальными исходниками иллюстраций; в статью добавляются
растровые снимки самих макетов, а не исполняемый HTML.

Для пяти практических статей (адаптация Mini App, первая форма, настройки
уведомлений, QR-билеты и PDF) исходники создаёт
`node content/guides/illustrations/build-practical-illustrations.mjs OUTPUT_DIRECTORY`.
Сборщик извлекает разметку из пакетов или `current.json`, отключает сетевые
скрипты и подставляет явно демонстрационные состояния. Он создаёт шесть
композиций, каждую в широком и вертикальном формате, и `manifest.json`.
Клавиатура и безопасные отступы обозначены как схемы, а не снимки устройств.

PDF и учебный QR готовит `render-practical-assets.php OUTPUT_DIRECTORY TOOLS_AUTOLOAD`.
Для этого локального помощника Dompdf 3.1.6+ и endroid/qr-code 6 устанавливаются
в отдельный каталог инструментов; зависимости приложения менять не нужно.
PDF использует Blade-шаблон и данные Tinker из статьи. QR не связан с рабочей БД.
`export-practical-illustrations.py pdf OUTPUT_DIRECTORY` через pypdfium2 и Pillow
готовит фрагменты PDF для композиций. При изменении шаблона область обрезки
нужно проверить заново.

HTML-композиции снимаются в браузере при ширине 1080 и 390 px. Для экспорта
создаётся `captures.json`: записи `manifest.json` плюс `capture` (имя JPG)
и `height` (высота `.canvas` в CSS-пикселях). Снимки должны быть в масштабе 1:1.
`export-practical-illustrations.py captures OUTPUT_DIRECTORY` сохраняет JPEG
в `public/images/guides/` и фактические размеры в `exported.json`.
После визуальной проверки размеры переносятся в соответствующие `guide-image`.
Эти команды нужны только для подготовки иллюстраций, не для deployment.

Для inline-поиска, выбора между чатом и Mini App, корзины, n8n, операторской
поддержки, записи, согласования, отправки карточки, выбора чата, BotFather
и пагинации каталога используются отдельные помощники:

```text
php content/guides/illustrations/render-scenario-assets.php OUTPUT_DIRECTORY
node content/guides/illustrations/build-scenario-illustrations.mjs OUTPUT_DIRECTORY
node content/guides/illustrations/capture-scenario-illustrations.mjs OUTPUT_DIRECTORY PLAYWRIGHT_MODULE
```

На Windows для первого шага используется `php85`. `PLAYWRIGHT_MODULE` — путь
к локально установленному модулю Playwright; зависимости приложения менять
не требуется. Снимки делает headless Edge; другой установленный канал можно
задать через `ILLUSTRATION_BROWSER`. Сборщик использует пакеты, затем `current.json`.

PHP-помощник выполняет две самостоятельные функции статьи: `inlineProducts`
и `catalogPage`. Каталог использует SQLite в памяти и шесть учебных товаров;
сетевых вызовов нет. Остальные изображения — явно подписанные схемы по тексту
и кнопкам статьи. Для `shareMessage` используется увеличенный фрагмент `share.html`
с отключёнными сетевыми скриптами. Корзина подписана как пример интерфейса,
поскольку статья содержит серверное ядро, а не готовую HTML-форму.

Настоящие окна Telegram, BotFather и n8n в этих схемах не воспроизводятся.
У n8n две композиции: workflow и памятка полей HTTP Request. Их названия
сверяются с [документацией узла](https://docs.n8n.io/integrations/builtin/core-nodes/n8n-nodes-base.httprequest/).
Механика inline-поиска и создания бота описана в документации
[Inline Bots](https://core.telegram.org/bots/inline) и
[BotFather](https://core.telegram.org/bots/features#botfather).

Экспорт создаёт 24 JPEG: 12 композиций в широком и мобильном формате для
11 статей. `exported.json` содержит фактические размеры JPEG, пути и подписи
для блоков `guide-image`. Мобильные композиции опускают повторную длинную
подпись внутри рисунка; полная подпись остаётся под изображением в статье.
При пересборке нужно повторно проверить читаемость в колонке статьи,
фактические размеры и перенести обновлённые значения в пакеты.

При первой выкладке поддержки `guide-image` на сервер нужно доставить код блока,
файлы изображений и собрать ассеты (`npm run build`). Если поддержка уже выложена,
для новых иллюстраций достаточно файлов изображений и импорта пакетов статей.
Сборка ассетов при неизменных CSS/JS и миграции для этого не нужны.
Обычный импорт/экспорт сохраняет настройки иллюстраций вместе со статьёй.

Иллюстрации для базы знаний, Google Calendar и черновика формы готовятся отдельно:

```text
node content/guides/illustrations/build-knowledge-calendar-drafts.mjs OUTPUT_DIRECTORY
node content/guides/illustrations/capture-scenario-illustrations.mjs OUTPUT_DIRECTORY PLAYWRIGHT_MODULE
```

Сборщик берёт HTML формы из пакета статьи (после переноса — из `current.json`).
Две композиции — подписанные схемы; третья показывает фрагменты формы с
демонстрационными данными. Сетевых запросов к Telegram и Google при подготовке нет.
Результат — шесть JPEG, включая мобильные версии. Размеры из `exported.json`
нужно перенести в блоки `guide-image` после пересборки и визуальной проверки.
При публикации обновлённых пакетов доставляются также соответствующие каталоги
`public/images/guides/`; при неизменном оформлении сборка ассетов не требуется.

Иллюстрации для загрузки файлов, ежедневного отчёта, отзывов и нескольких ботов:

```text
php content/guides/illustrations/render-daily-report.php OUTPUT_DIRECTORY
node content/guides/illustrations/build-uploads-reports-reviews-bots.mjs OUTPUT_DIRECTORY
node content/guides/illustrations/capture-scenario-illustrations.mjs OUTPUT_DIRECTORY PLAYWRIGHT_MODULE
```

Локально на Windows первая команда запускается через `php85`. PHP-помощник
вызывает чистую функцию форматирования отчёта из статьи на учебных данных.
Макет загрузки использует HTML формы и явно условное изображение устройства;
остальные композиции — подписанные схемы. Сетевых вызовов к Telegram нет.
Экспорт создаёт восемь JPEG: четыре широкие и четыре мобильные версии.
После пересборки нужно проверить читаемость и перенести размеры из
`exported.json` в блоки `guide-image`. Для публикации требуются пакеты и изображения,
без сборки ассетов при неизменном оформлении.

Иллюстрации для многоязычного диалога и мини-курса:

```text
php content/guides/illustrations/render-language-course.php OUTPUT_DIRECTORY
node content/guides/illustrations/build-language-course.mjs OUTPUT_DIRECTORY
node content/guides/illustrations/capture-scenario-illustrations.mjs OUTPUT_DIRECTORY PLAYWRIGHT_MODULE
```

Локально на Windows PHP-помощник запускается через `php85`. Он выполняет только
словари и чистый каталог уроков из пакетов, без БД и сетевых обращений.
Результат — две подписанные поясняющие схемы в широком и мобильном форматах,
всего четыре JPEG. Размеры из `exported.json` нужно перенести в `guide-image`
после пересборки. Для публикации доставляются соответствующие JSON и каталоги
`public/images/guides/`, сборка ассетов при неизменном оформлении не требуется.

Иллюстрации для калькулятора, бонусной карты, листа ожидания и задач:

```text
node content/guides/illustrations/build-calculator-loyalty-waiting-tasks.mjs OUTPUT_DIRECTORY
node content/guides/illustrations/capture-scenario-illustrations.mjs OUTPUT_DIRECTORY PLAYWRIGHT_MODULE
```

Сборщик извлекает HTML двух Mini Apps из статей и подставляет учебные ответы API;
остальные две композиции — подписанные схемы. Сетевых запросов к Telegram нет.
Восемь JPEG включают широкие и мобильные варианты. После пересборки нужно
проверить читаемость и перенести размеры из `exported.json` в `guide-image`.
Для публикации требуются пакеты и соответствующие `public/images/guides/`;
при неизменных CSS/JS сборка ассетов не нужна.

### Подготовка и перенос статьи

1. Читать `current.json` и подготовленные пакеты: проверять тему и slug,
   не путать подтверждённый каталог с ещё не перенесённым материалом.
2. Для новой статьи создавать пакет только с ней. При правке брать текущую
   запись из каталога и сохранять остальные поля, URL и якоря.
3. Проверять пакет через `guides:import --dry-run`, затем импортировать
   в известную локальную/изолированную БД и проверять реальную страницу.
4. Пользователь синхронизирует пакет через Git на dev, затем на production
   и в каждом окружении запускает импорт прямо из
   `content/guides/packages/<slug>.json`: сначала с `--dry-run`, после проверки
   вывода — без него. Копия в `storage/app` для подготовленного пакета не нужна.
   Порядок команд и проверки описаны в `docs/seo.md` workspace.
   Если после подготовки статью изменили в админке dev, обновить пакет её свежим
   экспортом перед переносом на production.
5. После подтверждения успешного production-импорта обновлять `current.json`
   только применёнными записями по slug; неперенесённые пакеты не включать.
   Обновлять описание источника выше. Если после подготовки были правки через
   админку, сначала получить свежий экспорт, а не накатывать старую копию.
6. Сверить все записи применённого пакета с обновлённым `current.json`.
   После подтверждения production-публикации и совпадения всех записей удалить
   именно этот файл из `packages`. Не удалять пакеты после одного `--dry-run`
   или dev-импорта, при частично подтверждённом переносе либо несовпадении данных.
   `current.json` сохранять. Это шаг работы агента, не побочный эффект команды
   `guides:import`. Удаление отслеживаемого файла попадёт на серверы с Git-выкладкой;
   отдельные загруженные копии в `storage/app` удаляет пользователь точечно.

Отдельных черновиков, истории версий и согласования публикации в приложении
нет. Подготовленный пакет и проверка на dev — этапы работы, не статусы статьи.
Для запроса только тем достаточно этой копии при сохранении договорённости
об отсутствии сторонних правок. Новый экспорт каждый раз не требуется.

Не импортировать весь `current.json` в production ради одной статьи.
Не добавлять автоматический импорт каталога в deployment и не изменять
`database/data/guides.json`: это неизменяемый снимок первичной миграции.

Иллюстрации к оплате физических товаров, каталогу из таблиц, RSS и потоковым
ответам — концептуальные схемы, не снимки интерфейсов. Исходник:
`illustrations/build-payments-sheets-rss-streaming.mjs`. Для пересборки из корня приложения:

```bash
node content/guides/illustrations/build-payments-sheets-rss-streaming.mjs storage/app/illustrations-payments-sheets-rss-streaming
node content/guides/illustrations/capture-scenario-illustrations.mjs storage/app/illustrations-payments-sheets-rss-streaming /path/to/playwright
```

Каждая схема имеет отдельную мобильную версию. Для публикации нужны JSON-пакеты
и соответствующие каталоги `public/images/guides/<slug>/`; импорт на серверы
ещё не подтверждён.

### Примеры на GitHub

Исходники калькулятора стоимости и каталога Google Таблиц находятся в отдельном
публичном репозитории [Alexby8/botgate-examples](https://github.com/Alexby8/botgate-examples).
Структура: `examples/<slug-статьи>/<язык>/`. Общий README статьи перечисляет
реализации, README внутри `php` содержит требования и запуск. HTML/JavaScript
Mini App входят в соответствующий PHP-пример. Новые языки добавляются рядом.

В статье остаются объяснение процесса и основные связанные фрагменты. Полная
настройка расширений, служебные функции и инструкции запуска находятся в README
и исходниках GitHub. Верхняя карточка ведёт на папку статьи в `main`, где есть
описание и выбор реализации. Ссылки на разобранные файлы фиксируются по проверенному
коммиту, когда важно точное соответствие коду в тексте. При изменении примера
нужно проверить и обновить соответствующую статью.

Если готовый пример полезен читателю, в конце `intro.content` добавляется блок
`guide-example`. Он выводит карточку под введением, перед оглавлением. Без этого
блока карточки нет; отдельный проект для каждой статьи не требуется.

```json
{
  "type": "customBlock",
  "attrs": {
    "id": "guide-example",
    "config": {
      "title": "Калькулятор стоимости услуг",
      "description": "Форма, серверный расчёт и сохранение заявки.",
      "language": "PHP",
      "url": "https://github.com/Alexby8/botgate-examples/tree/main/examples/telegram-mini-app-service-calculator"
    }
  }
}
```

Для верхней карточки используйте `main` и папку статьи без суффикса `/php`.
Разрешены также ссылки с проверенным SHA из 40 символов на папку примера или
его языка в `Alexby8/botgate-examples`; текстовые поля экранируются. Карточка доступна
в редакторе как «Пример на GitHub», импорт и экспорт сохраняют её настройки.
При первой выкладке нужны PHP-код блока, Blade-шаблон, `public/images/github-mark.svg`
и сборка CSS (`npm run build`), затем импорт двух обновлённых пакетов.
Миграция базы не требуется. Последующие карточки используют тот же блок.

Это независимые проекты читателя, а не модули Laravel-приложения BotGate.
Composer-зависимости примеров устанавливаются только в их языковых папках.
Дубли исходников, прежние ZIP и их сборщик удалены из основного приложения;
поддерживать вторую копию здесь не нужно. Изображения статей остаются в public.

Для пересборки иллюстрации калькулятора клонируйте `botgate-examples` рядом с
`bot-gate-main`. `build-calculator-loyalty-waiting-tasks.mjs` также принимает путь
к его каталогу `examples` третьим аргументом. Используйте версию примера,
указанную в статье, чтобы иллюстрация соответствовала коду.

URL, даты публикации и прежние якоря двух статей сохранены. Для обновления сайта
достаточно доставки изменённых файлов и импорта соответствующих JSON-пакетов;
загрузка примеров на сервер сайта больше не нужна. Импорт на production пока
не подтверждён. При выкладке удалений учитывайте порядок: сначала обновлённые
статьи со ссылками GitHub, затем удаление прежних ZIP на сервере.
