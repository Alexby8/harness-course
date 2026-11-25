# BotGate — Integration Guide

BotGate is a proxy service for Telegram Bot API. Instead of calling `api.telegram.org` directly,
your app sends requests to BotGate, which forwards them from a foreign server and returns the response as-is.

## Quick Start

1. Register at BotGate and go to the dashboard.
2. Add your bot: paste the token from [@BotFather](https://t.me/BotFather).
3. Copy your API key (`bg_live_...`) from the dashboard.
4. Copy your Bot Public ID (`bot_xxxxxxxxxxxx`) from the bot card.
5. Replace `api.telegram.org` with the BotGate proxy URL in your code.

---

## Authentication

All requests must include the `Authorization` header:

```
Authorization: Bearer bg_live_xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx
```

Keep the key secret. Regenerate it in the dashboard if compromised.

---

## Proxy API

### Endpoint

```
POST https://bot-gate.ru/api/v1/bots/{botPublicId}/{method}
```

- `{botPublicId}` — bot identifier from the bot card, e.g. `bot_8Kx91aPqZ2`
- `{method}` — any Telegram Bot API method, e.g. `sendMessage`, `sendPhoto`
- `getUpdates` is **blocked** — use webhook instead

### Send a message (JSON)

```bash
curl -X POST https://bot-gate.ru/api/v1/bots/bot_xxxxxxxxxxxx/sendMessage \
  -H "Authorization: Bearer bg_live_xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx" \
  -H "Content-Type: application/json" \
  -d '{
    "chat_id": 123456789,
    "text": "Hello from BotGate!",
    "parse_mode": "HTML"
  }'
```

### Send a photo (multipart)

```bash
curl -X POST https://bot-gate.ru/api/v1/bots/bot_xxxxxxxxxxxx/sendPhoto \
  -H "Authorization: Bearer bg_live_xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx" \
  -F "chat_id=123456789" \
  -F "photo=@/path/to/photo.jpg" \
  -F "caption=Photo caption"
```

### Response format

BotGate returns the Telegram response unchanged:

```json
{
  "ok": true,
  "result": {
    "message_id": 42,
    "from": { "id": 987654321, "is_bot": true, "first_name": "MyBot", "username": "my_bot" },
    "chat": { "id": 123456789, "type": "private" },
    "date": 1700000000,
    "text": "Hello from BotGate!"
  }
}
```

### Rate limits

| Scope | Limit |
|---|---|
| Per API key | 600 requests/min |
| Per bot | 300 requests/min |

Exceeding the limit returns HTTP `429`.

---

## Downloading Files

Telegram stores files on its servers and provides a `file_path` to download them.
BotGate proxies this download so you never need to expose the bot token on the client side.

### Step 1 — get file_path

Call the `getFile` method with the `file_id` from any media object (photo, document, voice, etc.):

```bash
curl -X POST https://bot-gate.ru/api/v1/bots/bot_xxxxxxxxxxxx/getFile \
  -H "Authorization: Bearer bg_live_xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx" \
  -H "Content-Type: application/json" \
  -d '{"file_id": "BQACAgIAAxkBAAIBs2..."}'
```

Response:

```json
{
  "ok": true,
  "result": {
    "file_id": "BQACAgIAAxkBAAIBs2...",
    "file_size": 14987,
    "file_path": "documents/file_123.pdf"
  }
}
```

### Step 2 — download the file

```
GET https://bot-gate.ru/api/v1/bots/{botPublicId}/files/{file_path}
Authorization: Bearer {api_key}
```

The response is a binary stream with the correct `Content-Type` header.

```bash
curl -O -J \
  -H "Authorization: Bearer bg_live_xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx" \
  "https://bot-gate.ru/api/v1/bots/bot_xxxxxxxxxxxx/files/documents/file_123.pdf"
```

> **Note:** Telegram file paths expire after a few hours. Do not cache `file_path` long-term — call `getFile` again when needed.

---

## Webhook (optional)

BotGate can receive updates from Telegram and deliver them to your server.
This is useful if a direct webhook from Telegram is unstable or unavailable from Russia.

### How it works

1. Telegram sends an update to BotGate.
2. BotGate saves the event and queues a delivery job.
3. BotGate makes a `POST` request to your Webhook URL.
4. On failure — up to 3 attempts with delays: 0 s, 60 s, 300 s.

### Setup

On the bot page in the dashboard, find the **Webhook** section and enter your delivery URL.
BotGate will register itself with Telegram automatically.

### Incoming request headers

```
Content-Type: application/json
User-Agent: BotGate Webhook
X-BotGate-Bot-Id: bot_xxxxxxxxxxxx
X-BotGate-Event-Id: 42
X-BotGate-Signature: <hmac_sha256_hex>
```

### Signature verification

`X-BotGate-Signature` is HMAC-SHA256 of the raw request body, signed with your bot's `webhook_secret`
(shown in the bot card). Always verify the signature before processing the payload.

```php
$payload  = file_get_contents('php://input');
$secret   = 'your_webhook_secret';
$expected = hash_hmac('sha256', $payload, $secret);
$received = $_SERVER['HTTP_X_BOTGATE_SIGNATURE'] ?? '';

if (!hash_equals($expected, $received)) {
    http_response_code(403);
    exit('Invalid signature');
}

$update = json_decode($payload, true);
// process $update...
```

Your server must respond with any HTTP `2xx` within **10 seconds**, otherwise the delivery is retried.

### Payload format

Standard Telegram Update object:

```json
{
  "update_id": 100500,
  "message": {
    "message_id": 1,
    "from": { "id": 123456789, "first_name": "Ivan", "username": "ivan" },
    "chat": { "id": 123456789, "type": "private" },
    "date": 1700000000,
    "text": "/start"
  }
}
```

---

## Error Codes

| HTTP | Reason | Fix |
|---|---|---|
| `401` | Missing or invalid API key | Check `Authorization: Bearer ...` header |
| `403` | Bot disabled or method forbidden | Enable the bot; don't use `getUpdates` |
| `404` | Bot not found | Check `botPublicId` in the URL |
| `429` | Rate limit exceeded | Reduce request frequency (max 600/min) |
| `502` | Telegram unreachable | Retry after a few seconds |
