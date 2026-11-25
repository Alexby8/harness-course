---
name: wiki-query
description: Ответь на вопрос о правилах и поведении BotGate по базе знаний, первоисточникам и коду. Приведи ссылки на подтверждения.
---

1. Read `llm-wiki/wiki/index.md`, then the relevant pages, then the pages they link to.
2. Check numbers, thresholds and exceptions against the cited source lines and the code.
3. Answer with a short conclusion first, then the reasoning with links (`[[page]]`, `[DOC:lines]`,
   `[code:path#Symbol]`). For calculations, show the formula and the intermediate values.
4. A question changes nothing by itself: answer and stop. Edit pages and write a `query` entry in
   `llm-wiki/wiki/log.md` only when the base actually changed — you had to read beyond the wiki
   (that is a gap: fill it) or the answer is a valuable cross-cutting result worth keeping in
   `llm-wiki/wiki/analyses/` (offer it first).


## BotGate

Для поиска по источникам и коду используй проектный MCP qmd или локальный CLI `node llm-wiki/tools/qmd/node_modules/@tobilu/qmd/bin/qmd` из корня проекта. Проверь полный документ и реализацию. Если первичный ingest ещё не выполнялся, явно обозначь это. Изменение базы по результату запроса согласуй отдельно.
