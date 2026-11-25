---
title: Отправка и скачивание файлов
type: concept
summary: Как передать несколько вложений и по какому ответу читать содержимое файла.
sources: [BG-CONTRACT, BG-INTEGRATION]
updated: 2026-11-25
---

# Отправка и скачивание файлов

Загрузка в Telegram идёт через публичный маршрут метода. Laravel отделяет текстовые поля от файлов и передаёт имена полей и исходные имена файлов. Для альбома ссылки attach:// в описании медиа должны соответствовать именам multipart-полей. [BG-CONTRACT:58-70 «с двумя файловыми полями … успешно проходит через публичный … route»] [code:app/Http/Controllers/Api/V1/TelegramProxyController.php#proxyMultipart] [code:tests/Feature/Api/PublicTelegramApiCompatibilityTest.php#test_multiple_files_and_attach_references_are_preserved]

В обратную сторону сначала запрашивают getFile, затем скачивают по возвращённому file_path с тем же доступом, что у [[public-api]]. Ответ читается потоком. При наличии upstream-заголовков клиент получает Content-Type и Content-Length. Недоступный файл даёт 404, ошибка соединения — 502. [BG-CONTRACT:72-79 «бинарный streaming response без загрузки всего файла в память»] [code:app/Http/Controllers/Api/V1/TelegramFileController.php#download] [code:tests/Feature/Api/PublicTelegramApiCompatibilityTest.php#test_file_download_streams_body_and_upstream_headers]

Клиент получает file_id из Telegram Update, запрашивает getFile и подставляет file_path в GET /api/v1/bots/{botPublicId}/files/{file_path}. Если путь устарел, запрашивает новый. [BG-INTEGRATION:120-135 «call getFile again when needed»]
