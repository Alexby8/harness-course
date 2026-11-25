---
name: wiki-lint
description: По запросу на проверку базы знаний BotGate проверь структуру, ссылки, цитаты, противоречия и соответствие страниц текущему коду.
---

1. `node llm-wiki/tools/wiki.mjs lint` — fix the errors and review the warnings.
2. `node llm-wiki/tools/wiki.mjs status` — list new sources. Compare the source diff for changes under existing IDs.
3. Semantic checks:
   - contradictions between pages;
   - statements made obsolete by newer sources;
   - concepts that have no page of their own;
   - mismatches with the code.

   Anything that needs a human decision becomes a `> [!question]` callout.
4. Suggest new questions to investigate and new sources to look for.
5. `wiki.mjs index`, then `wiki.mjs lint`, then append a `lint` entry to the log.


## BotGate

Для изменённого кода проверь связанные страницы и контракт по исходным файлам. До первого ingest предупреждения о необработанных документах ожидаемы. QMD-поиск не заменяет проверку ссылок, цитат и противоречий.
