<?php

declare(strict_types=1);

use App\Http\Controllers\Api\V1\TelegramFileController;
use App\Http\Controllers\Api\V1\TelegramProxyController;
use App\Http\Controllers\Api\V1\TelegramWebhookController;
use App\Http\Middleware\AuthenticateApiKey;
use Illuminate\Support\Facades\Route;

// Входящие updates от Telegram (без авторизации — защищён secret в URL)
Route::post(
    '/v1/telegram/webhook/{botPublicId}/{secret}',
    [TelegramWebhookController::class, 'receive']
)->name('telegram.webhook');

// Прокси API для пользователей
Route::middleware([AuthenticateApiKey::class, 'throttle:api_key', 'throttle:bot'])->group(function (): void {
    // Скачивание файлов из Telegram (регистрируется до прокси-маршрута)
    Route::get(
        '/v1/bots/{botPublicId}/files/{file_path}',
        [TelegramFileController::class, 'download']
    )->where('file_path', '.+');

    Route::any(
        '/v1/bots/{botPublicId}/{method}',
        [TelegramProxyController::class, 'handle']
    )->where('method', '[a-zA-Z]+');
});
