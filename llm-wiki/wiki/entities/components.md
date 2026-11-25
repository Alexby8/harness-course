---
title: Компоненты BotGate
type: entity
summary: За что отвечают Laravel, прокси-нода, клиентские библиотеки и репозиторий примеров.
sources: [BG-AGENT-ARCH, BG-ARCH]
updated: 2026-11-25
---

# Компоненты BotGate

Laravel хранит пользователей, ботов и рабочее состояние. Отдельный Go-сервис передаёт запросы в Telegram. Код PHP SDK и его интеграции с Laravel ведётся в своих репозиториях. Многофайловый пример к статье живёт в каталоге примеров по slug и языку. Это важно, когда читатель спрашивает, где менять код примера. [BG-AGENT-ARCH:3-15 «Исходники поддерживаются здесь … Не возвращать дубли примеров и ZIP в основное приложение»]

В учебной копии отправку на ноду видно со стороны клиента Laravel. [code:app/Services/Proxy/ProxyNodeManager.php#sendJson] Границы проверки соседних сервисов приведены в [[overview]].

Для исходящего запроса Laravel выбирает включённую online-ноду с наименьшим числом priority, при равенстве — по ID. Если основная нода уже отмечена offline, следующий запрос может пойти на резервную. При тайм-ауте начатой операции автоматическая отправка того же запроса на вторую ноду могла бы повторить действие в Telegram. [BG-ARCH:38-44 «операция могла уже выполниться»] [code:app/Models/ProxyNode.php#scopeRoutable] [code:tests/Feature/Services/ProxyNodeManagerTest.php#test_it_does_not_retry_an_ambiguous_connection_failure_on_another_node]

Входящий адрес выбирается через DNS общего webhook-домена. Nginx передаёт запрос основному Laravel. Параметры исходящей ноды в Filament эту маршрутизацию не меняют. Роль Nginx описана в рабочей архитектуре, а в учебной копии проверяется построение адреса: [BG-ARCH:52-71 «Laravel … управляют исходящим транспортом, но не переключают входящие webhook»] [code:app/Services/TelegramWebhookUrl.php#forBot] [code:tests/Feature/Services/TelegramWebhookUrlTest.php#test_default_url_and_explicit_origin_share_exact_bot_route]. Далее действует [[webhook-ingress]].
