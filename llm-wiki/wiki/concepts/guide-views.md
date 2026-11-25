---
title: Как считается просмотр руководства
type: concept
summary: Почему просмотр статьи может учитываться при выключенной Метрике.
sources: [BG-CONSENT]
updated: 2026-11-25
---

# Как считается просмотр руководства

При первом показе опубликованной статьи в видимой вкладке JavaScript отправляет отдельный POST. Обычный GET статьи или открытие каталога не увеличивает счётчик. Выбор в [[analytics-consent]] управляет Метрикой и рекламной привязкой. [BG-CONSENT:28-32 «Счётчик работает независимо от Яндекс.Метрики и выбора в панели»] [code:app/Http/Controllers/GuideController.php#recordView] [code:tests/Feature/GuideViewsTest.php#test_only_published_articles_are_counted_even_when_metrika_is_disabled]

Ключ Redis строится из ID статьи и существующей сессии. Отметка действует 86 400 секунд от первого просмотра. Повторный визит в той же сессии не продлевает окно. Итог попадает в дневную строку статьи. Если запись в БД не удалась, процесс освобождает свою отметку, чтобы следующая попытка могла посчитать просмотр. [BG-CONSENT:30-33 «живёт 86 400 секунд и при повторе не продлевается»] [code:app/Services/Guides/GuideViewCounter.php#record] [code:tests/Feature/GuideViewsTest.php#test_window_is_24_hours_and_a_duplicate_does_not_extend_it]
