<?php

declare(strict_types=1);

return [
    'webhook' => [
        'base_url' => env('TELEGRAM_WEBHOOK_BASE_URL'),
        'allowed_base_urls' => array_values(array_filter(array_map('trim', explode(',', (string) env('TELEGRAM_WEBHOOK_ALLOWED_BASE_URLS', ''))))),
    ],

    'proxy' => [
        'connect_timeout' => (float) env('PROXY_CONNECT_TIMEOUT', 3),
        'health_timeout' => (float) env('PROXY_HEALTH_TIMEOUT', 5),
        'health_history_retention_days' => (int) env('PROXY_HEALTH_HISTORY_RETENTION_DAYS', 90),
        'json_timeout' => (float) env('PROXY_JSON_TIMEOUT', 35),
        'multipart_timeout' => (float) env('PROXY_MULTIPART_TIMEOUT', 95),
        'file_timeout' => (float) env('PROXY_FILE_TIMEOUT', 95),
        'test_bot_public_id' => env('PROXY_TEST_BOT_PUBLIC_ID'),
        'test_chat_id' => env('PROXY_TEST_CHAT_ID'),
    ],

    'rate_limits' => [
        'api_key_per_minute' => env('RATE_LIMIT_API_KEY', 600),
        'bot_per_minute' => env('RATE_LIMIT_BOT', 300),
    ],

    'forbidden_methods' => [
        'getUpdates',
    ],

    'yandex_metrika_id' => env('YANDEX_METRIKA_ID', '109789253'),
];
