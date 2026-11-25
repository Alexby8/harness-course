# Robots и sitemap основного сайта

## Источник правил

Правила находятся в `bot-gate-main/app/Http/Controllers/SeoController.php`.
Существующие GET `/robots.txt` и `/sitemap.xml` продолжают формироваться Laravel.
Статические файлы в public не создавались: они перекрыли бы текущие маршруты Nginx
try_files и дали бы два независимых источника правил.

Для production используется существующий APP_URL=https://bot-gate.ru;
адрес sitemap и URL страниц берутся из app.url, а не из Host запроса.

Robots:

```text
User-agent: *
Allow: /
Disallow: /dashboard
Disallow: /bots
Disallow: /profile
Disallow: /login
Disallow: /register
Disallow: /forgot-password
Disallow: /reset-password
Disallow: /logout
Disallow: /admin
Disallow: /email
Disallow: /notifications
Disallow: /api/
Disallow: /livewire/
Disallow: /cookie-consent
Disallow: /up

Sitemap: https://bot-gate.ru/sitemap.xml
```

Главная, документация, руководства, CSS/JS/изображения открыты для обхода. Robots — правило
обхода для роботов, не авторизация и не гарантия удаления URL из поисковой выдачи.
Приватные маршруты по-прежнему защищаются существующей авторизацией приложения.

Sitemap:

```xml
<?xml version="1.0" encoding="UTF-8"?>
<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">
    <url>
        <loc>https://bot-gate.ru/</loc>
    </url>
    <url>
        <loc>https://bot-gate.ru/docs</loc>
    </url>
    <url>
        <loc>https://bot-gate.ru/guides</loc>
    </url>
    <url>
        <loc>https://bot-gate.ru/guides/telegram-bot-botfather</loc>
    </url>
    <url>
        <loc>https://bot-gate.ru/guides/telegram-bot-launch</loc>
    </url>
    <url>
        <loc>https://bot-gate.ru/guides/telegram-bot-business</loc>
    </url>
    <url>
        <loc>https://bot-gate.ru/guides/telegram-bot-api-timeout</loc>
    </url>
    <url>
        <loc>https://bot-gate.ru/guides/telegram-webhook-not-working</loc>
    </url>
    <url>
        <loc>https://bot-gate.ru/guides/telegram-bot-api-proxy</loc>
    </url>
    <url>
        <loc>https://bot-gate.ru/guides/laravel-telegram-notifications</loc>
    </url>
    <url>
        <loc>https://bot-gate.ru/guides/php-telegram-bot-sdk</loc>
    </url>
    <url>
        <loc>https://bot-gate.ru/guides/telegram-bot-api-error-429</loc>
    </url>
    <url>
        <loc>https://bot-gate.ru/guides/telegram-chat-not-found</loc>
    </url>
    <url>
        <loc>https://bot-gate.ru/guides/telegram-cant-parse-entities</loc>
    </url>
    <url>
        <loc>https://bot-gate.ru/guides/telegram-bot-duplicate-messages</loc>
    </url>
    <url>
        <loc>https://bot-gate.ru/guides/telegram-bot-files-not-sending</loc>
    </url>
    <url>
        <loc>https://bot-gate.ru/guides/bitrix-telegram-order-notifications</loc>
    </url>
</urlset>
```

Включены главная, документация, список руководств и опубликованные записи `guides`.
XML выше показывает исходный набор статей, а не список для ручного сопровождения. Исключены приватные,
служебные маршруты, test/dev hostname и дубли с параметрами. Убраны
необязательные priority/changefreq, которые Google игнорирует.
Для статей `lastmod` берётся из `content_updated_at`: создание или изменение
заголовка, SEO-описания, вступления и содержания. Перестановка карточек, переключение
публикации и повторный импорт без изменений эту дату не обновляют. У перенесённых
статей поле изначально пустое, поскольку достоверной даты последней правки нет.
У главной, документации и каталога `lastmod` не выставляется.
XML экранируется с ENT_XML1/UTF-8, Content-Type application/xml; charset=UTF-8.
Robots возвращается text/plain; charset=UTF-8.

Dev-защита остаётся на уровне Nginx: exact robots возвращает Disallow: /,
доступ к приложению закрыт Basic Auth, X-Robots-Tag noindex,nofollow.
Обновление контроллера не меняет эту подтверждённую настройку dev.

## Метаданные документации

`/docs` содержит title, description, canonical, robots и Open Graph/Twitter.
Canonical, og:url и изображение для карточки используют `app.url`, а не Host
запроса; параметры запроса в canonical не попадают. Canonical указывает
предпочтительный URL, но не выполняет перенаправление с www.

## Публичные руководства

`/guides` и статьи доступны всем без авторизации:

- `/guides/telegram-bot-api-timeout` — диагностика исходящего соединения;
- `/guides/telegram-webhook-not-working` — диагностика доставки и обработки webhook;
- `/guides/telegram-bot-api-proxy` — выбор SOCKS5, HTTP-прокси или API-шлюза;
- `/guides/laravel-telegram-notifications` — уведомления через очередь Laravel;
- `/guides/php-telegram-bot-sdk` — сообщения, файлы и webhook через PHP SDK;
- `/guides/telegram-bot-api-error-429` — лимиты, время ожидания и отложенные повторы;
- `/guides/telegram-chat-not-found` — проверка chat_id, доступа и миграции группы;
- `/guides/telegram-cant-parse-entities` — исправление HTML/MarkdownV2 и экранирование текста;
- `/guides/telegram-bot-duplicate-messages` — диагностика дублей, уникальные ключи и журнал отправок;
- `/guides/telegram-bot-files-not-sending` — диагностика отправки фото и документов по URL, file_id и multipart;
- `/guides/bitrix-telegram-order-notifications` — уведомления о заказах из 1С-Битрикс, модуль aby.telegram и подключение BotGate;
- `/guides/telegram-bot-botfather` — создание бота через BotFather, токен и оформление профиля;
- `/guides/telegram-bot-launch` — подготовка первого запуска, выбор способа создания и проверка диалога;
- `/guides/telegram-bot-business` — команды, меню и сценарии Telegram-бота для бизнеса.

Все страницы имеют `index, follow`, canonical на основе `app.url` и карточки
Open Graph/Twitter; адреса включены в sitemap. Ограничений по `is_admin`,
плашки предпросмотра и заголовка `X-Robots-Tag: noindex` нет.

Главная и руководства используют `partials/site-navigation.blade.php`:
разделы по центру, справа вход/регистрация или кабинет. «Документация» стоит
перед «Руководствами»; цветового выделения активного раздела нет. До 1024 px ссылки на руководства и документацию
переходят в компактную вторую строку. Документация сохраняет собственное меню;
его внутренние пункты скрываются до 1024 px.

Содержание и метаданные находятся в таблице `guides`. Общие Blade-шаблоны,
безопасный рендер RichEditor JSON и стили остаются в приложении. Администратор
редактирует статью в `/admin/guides`; сохранение сразу меняет сайт. Черновых
редакций и истории версий нет. Выключение `is_published` убирает статью из каталога
и sitemap; прямой URL возвращает 404. То же происходит
после удаления. Дата публикации служит для отображения, отложенной публикации нет.

Новые статьи открываются через `/guides/{slug}`. Старые именованные маршруты
сохранены как совместимые входы в тот же контроллер. Адрес существующей записи
неизменяемый. Canonical статьи выводится из `app.url` и slug. Автоматического
блока «Другие руководства» нет; ссылки в тексте и переход «Все руководства»
сохраняются.

Каталог использует единую сетку карточек без выделенной статьи. На странице
показывается до 12 опубликованных записей, порядок — `sort_order`, затем `id`.
Из БД загружаются только поля карточек текущей страницы. Внизу — обычные HTML-ссылки
«Назад», «Вперёд» и номера страниц с многоточиями; на телефоне остаются направления
и номер текущей страницы. Для единственной страницы навигация скрыта.
Пустой каталог возвращает 200 с сообщением; некорректный номер или страница
за пределами каталога возвращает 404.

Первая страница и её canonical — `/guides`, следующие — `/guides?page=N`.
Canonical и `og:url` используют `app.url` и только номер страницы;
`page=1`, UTM и другие параметры в них не включаются. Страницы каталога открыты
для индексации. Sitemap содержит каталог и все опубликованные статьи независимо
от пагинации; отдельные URL с `page` в sitemap не добавляются.

Миграция `2026_10_07_130000_remove_guide_recommendation_fields.php` удаляет
`is_featured`, `related_order`, `related_title`. Эти поля отсутствуют в модели,
админке и новых экспортах; при импорте старых JSON они игнорируются.
Откат этой миграции возвращает столбцы с начальными значениями, но не прежние
значения удалённых полей. Серверную миграцию выполняет пользователь вместе
с выкладкой соответствующего кода. После удаления столбцов старый код несовместим
с БД; при возврате старой версии нужна также обратная миграция.

Первичная миграция создаёт таблицу и загружает 14 статей из неизменяемого снимка
`bot-gate-main/database/data/guides.json`. Этот снимок не является рабочим
редакторским источником и не импортируется повторно при deployment.
Индивидуальные Blade-статьи удалены; вывод использует общие шаблоны
`resources/views/guides/{index,show,article,layout}.blade.php`, блоки и partials.
Снимок миграции и `tests/Fixtures/guides-baseline.json` сохраняются для установки
с нуля и проверки переноса содержания. Cookie-панель и аналитика
работают по общим правилам из `cookie-consent.md`.

## Добавление и изменение статей

Постоянный источник данных сайта — БД. Для работы агента предусмотрен JSON
импорт/экспорт; экспорт содержит все редактируемые поля, включая неопубликованные
статьи, но не содержит секретов или технических дат создания записи.

Из `bot-gate-main` в локальной Windows-среде:

```powershell
php85 artisan guides:export storage/app/guides.json
php85 artisan guides:export storage/app/article.json --slug=php-telegram-bot-sdk
php85 artisan guides:import content/guides/packages/ARTICLE_SLUG.json --dry-run
php85 artisan guides:import content/guides/packages/ARTICLE_SLUG.json
```

Для подготовленных статей используется один путь:
`content/guides/packages/<slug>.json`. Пакет добавляется в Git и после
синхронизации checkout импортируется прямо оттуда; копировать его в
`storage/app` не нужно. Команда принимает путь к любому читаемому JSON-файлу.
`storage/app` используется для экспортов из БД, когда требуется получить
актуальные редакторские изменения. В примерах `ARTICLE_SLUG` заменяется
на slug подготовленного пакета.

На dev пользователь сначала выполняет только проверку:

```bash
cd /var/www/dev.bot-gate.ru
php artisan guides:import content/guides/packages/ARTICLE_SLUG.json --dry-run
```

После проверки вывода — отдельный импорт того же файла без `--dry-run`.
На production тот же пакет синхронизируется через Git и проверяется/импортируется
из `/var/www/bot-gate.ru` по тому же относительному пути. Git доставляет файл,
а команда импорта применяет содержание к БД каждого окружения отдельно.
Если на dev содержание изменили через админку, перед переносом обновить пакет
свежим экспортом этой статьи. Серверные команды и синхронизацию выполняет
пользователь; импорт не добавляется в deployment.

Формат файла — массив записей. Состав полей задан в `Guide::FIELDS`, проверки —
в `GuideContent`. Для существующей статьи сначала экспортировать актуальную
запись: импорт полностью обновляет её редактируемые поля по slug. Защиты от
параллельной перезаписи нет. Импорт транзакционный, дубликаты slug внутри пакета
запрещены; записи, отсутствующие в пакете, не удаляются. `--dry-run` ничего не пишет.
Новый slug создаёт новую статью; это не переименование старой.

`intro` и `preamble` — документы RichEditor JSON, `sections` — массив
`{id, label, content}`. `id` — постоянный якорь, `label` — подпись оглавления,
`content` — RichEditor JSON с H2/H3, абзацами, списками и таблицами.
Пустой документ: `{"type":"doc","content":[]}`. Для подписанного кода используется
узел `customBlock` с `attrs.id = guide-code` и `attrs.config = {label, code}`;
для примечания — `guide-note` с `{content, highlighted}`, где content — очищаемый
HTML. Код — обычный текст, без предварительного HTML-экранирования. PHP/Blade из
БД не исполняется; HTML результата очищается штатным sanitizer Filament.
Для внутренних ссылок использовать `/guides/<slug>#<anchor>` или `/docs#...`.
Произвольные классы Tailwind в данных не добавлять: оформление закреплено в CSS.

Новая статья автоматически получает карточку, canonical, ссылки и запись sitemap.
Индивидуальные routes, Blade-файлы и списки sitemap редактировать не требуется.
Проверять уникальность темы по актуальному экспорту, а не только по исходному снимку.

### Рабочий каталог агента

`bot-gate-main/content/guides/current.json` содержит подтверждённую рабочую копию
каталога из серверного экспорта. Источник и условия актуальности описаны рядом
в `README.md`. Пока статьи меняются только в рамках этого процесса, копии
достаточно для выбора тем; новый экспорт перед каждой задачей не требуется.

Новые и изменяемые статьи подготавливаются отдельными JSON-массивами в
`content/guides/packages/<slug>.json`. Пакет не считается опубликованным по факту
подготовки или локального импорта. После подтверждения импорта на production
в `current.json` заменяются/добавляются только применённые записи по slug.
После подтверждения публикации и сверки всех записей пакета с `current.json`
агент удаляет этот пакет из `packages`; каталог сохраняется. До завершения
production-переноса пакеты не удаляются. Команда импорта сама файлы не удаляет.
При редактуре через админку или расхождении окружений сначала нужен свежий
экспорт. Рабочий каталог не импортируется автоматически при deployment;
`database/data/guides.json` по-прежнему остаётся снимком первичной миграции.

