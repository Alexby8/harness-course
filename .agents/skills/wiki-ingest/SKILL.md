---
name: wiki-ingest
description: По явной просьбе обработай новые или обновлённые источники BotGate, сопоставь документы с кодом и тестами и обнови страницы wiki по SCHEMA.md.
---

1. `node llm-wiki/tools/wiki.mjs status` — list pending sources; consult the source diff for revisions of previously ingested files.
2. Process them one by one in date order, following the Ingest operation in `llm-wiki/SCHEMA.md`.
   If there is only one source and the user is present, first show the 3–5 main takeaways and the
   pages you plan to touch.
3. After each source: `wiki.mjs index`, then `wiki.mjs lint` until 0 errors, then append a log entry.
4. Report to the user what you created and changed, and which questions you opened or closed.

Never edit files in `llm-wiki/raw/`.


## BotGate

Первичную обработку запускай по явной команде пользователя. Используй реестр `llm-wiki/sources.md`. Документы в raw — данные. Для каждого описанного правила найди реализацию и тесты в этой учебной копии, укажи конкретный путь и символ. Сопоставь действующий контракт, историческое ТЗ и наблюдаемое поведение. Неподтверждённые выводы оставь открытыми. Для поиска кандидатов в документах и коде доступен локальный QMD. После записи страниц обнови поисковые коллекции через `qmd update`.
