---
title: Каталог руководств и поисковая индексация
type: concept
summary: Какие адреса попадают в sitemap и когда меняется дата обновления статьи.
sources: [BG-SEO]
updated: 2026-11-25
---

# Каталог руководств и поисковая индексация

Каталог показывает по 12 опубликованных статей в порядке sort_order, затем id. Номер за пределами каталога даёт 404. Основной URL первой страницы — /guides, последующих — /guides?page=N. Дополнительные параметры в canonical не попадают. [BG-SEO:165-177 «до 12 опубликованных записей … отдельные URL с page в sitemap не добавляются»] [code:app/Http/Controllers/GuideController.php#index] [code:tests/Feature/GuidesTest.php#test_paginated_catalog_metadata_keeps_only_its_page_number]

Laravel строит robots.txt и sitemap.xml из конфигурации приложения. Sitemap включает главную, документацию, каталог и опубликованные статьи со всех страниц. У статьи lastmod берётся из content_updated_at, если дата известна. Поля, влияющие на неё, перечислены в модели. Перестановка карточки, смена видимости и идентичный импорт эту дату сохраняют. [BG-SEO:100-110 «Перестановка карточек, переключение публикации и повторный импорт без изменений эту дату не обновляют»] [code:app/Http/Controllers/SeoController.php#sitemap] [code:app/Models/Guide.php#booted] [code:tests/Feature/GuidePublishingTest.php#test_content_date_only_changes_with_content_and_metadata_is_escaped]. Редакторский процесс описан в [[guide-publishing]].
