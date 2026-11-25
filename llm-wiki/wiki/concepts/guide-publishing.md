---
title: Публикация и обновление руководства
type: concept
summary: Откуда сайт берёт статью и в какой момент редакторские изменения видны читателю.
sources: [BG-AGENT-ARCH, BG-SEO]
updated: 2026-11-25
---

# Публикация и обновление руководства

Публичную статью контроллер читает из таблицы guides по slug с признаком is_published. Изменение в админке сразу влияет на страницу. Дата публикации отображается читателю, а доступность определяется флагом. Выключенная статья исчезает из каталога и sitemap и отвечает 404 по прямой ссылке. [BG-SEO:152-157 «Выключение is_published убирает статью из каталога и sitemap»] [code:app/Models/Guide.php#scopePublished] [code:tests/Feature/GuidePublishingTest.php#test_visibility_updates_page_catalog_and_sitemap_immediately]

Пакет содержит массив записей. Для изменения существующей статьи редактор сначала экспортирует её актуальную версию. Импорт обновляет редактируемые поля по slug в транзакции. Новый slug создаёт запись, пропущенные статьи сохраняются. Предварительный запуск guides:import с --dry-run проверяет пакет без записи. Файл current.json помогает выбрать новую тему. Публикацию определяет импорт в БД конкретного окружения. [BG-SEO:228-239 «Импорт транзакционный, дубликаты slug внутри пакета запрещены»] [BG-SEO:258-269 «Пакет не считается опубликованным по факту подготовки»] [code:app/Console/Commands/ImportGuidesCommand.php#handle] [code:tests/Feature/GuidePublishingTest.php#test_import_is_repeatable_atomic_and_export_round_trips]

Многофайловый пример можно вынести в отдельный репозиторий и дать читателю ссылку. Блок примера необязателен. [BG-AGENT-ARCH:8-12 «отдельный публичный Git-репозиторий примеров к статьям»] [code:tests/Feature/GuideExamplesTest.php#test_example_is_optional_and_renders_after_intro_before_contents] См. [[guide-discovery]] и [[guide-views]].
