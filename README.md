# Учебный BotGate

Самостоятельная учебная копия Laravel-приложения BotGate для курса AI Harness. Начальная версия использует публичные страницы, кабинет, API, материалы руководств и тесты исходного коммита `2d5391f`. Приложение работает с локальной SQLite.

## Первый запуск на Windows

Требуются Composer, Node.js с npm и PHP с PDO SQLite. Проверенная среда — PHP 8.5.10 и Node.js 24.11.1. В PowerShell из корня новой копии выполните команды ниже. На компьютере с несколькими версиями PHP сначала добавьте нужную версию в PATH текущего терминала.

```powershell
php -v
php --ri pdo_sqlite
Copy-Item .env.example .env
New-Item -ItemType File database/database.sqlite
composer install
php artisan key:generate
php artisan migrate
npm.cmd ci
npm.cmd run build
php artisan test
php artisan serve --host=127.0.0.1 --port=8000
```

Копирование шаблона, создание файла SQLite и генерация ключа нужны только при первом запуске в пустом окружении. При следующем запуске достаточно команды `php artisan serve --host=127.0.0.1 --port=8000`. Откройте [главную страницу](http://127.0.0.1:8000/) или [каталог руководств](http://127.0.0.1:8000/guides). Ссылка на локальную форму регистрации — [/register](http://127.0.0.1:8000/register).

Миграции загружают начальный набор руководств. Редакционные пакеты находятся в `content/guides`. PHPUnit использует отдельную SQLite в памяти. Настройки рабочих интеграций для локальной проверки публичных страниц не требуются.

## Два дополнительных руководства

После миграций в каталоге 14 статей. Из корня учебной копии загрузите два готовых пакета в локальную SQLite:

```powershell
php artisan guides:import content/guides/packages/telegram-mini-app-service-calculator.json
php artisan guides:import content/guides/packages/telegram-bot-google-sheets-catalog.json
```

Получится 16 опубликованных статей. Откройте [калькулятор услуг](http://127.0.0.1:8000/guides/telegram-mini-app-service-calculator) и [каталог бота из Google Таблиц](http://127.0.0.1:8000/guides/telegram-bot-google-sheets-catalog). Повторный импорт тех же пакетов сохраняет число записей.
