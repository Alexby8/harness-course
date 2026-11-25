<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Enums\BotStatus;
use App\Http\Controllers\Controller;
use App\Models\Bot;
use App\Services\TelegramService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\StreamedResponse;

final class TelegramFileController extends Controller
{
    public function __construct(
        private readonly TelegramService $telegram,
    ) {}

    public function download(Request $request, string $botPublicId, string $filePath): StreamedResponse|JsonResponse
    {
        $bot = Bot::where('public_id', $botPublicId)->first();

        if ($bot === null || $bot->user_id !== Auth::id()) {
            return response()->json(['ok' => false, 'error' => 'Бот не найден'], 404);
        }

        if ($bot->status !== BotStatus::Active) {
            return response()->json(['ok' => false, 'error' => 'Бот отключён'], 403);
        }

        $token = $bot->telegram_token_encrypted;

        try {
            $telegramResponse = $this->telegram->getFileStream($token, $filePath);
        } catch (\Throwable) {
            return response()->json(['ok' => false, 'error' => 'Ошибка соединения с Telegram'], 502);
        }

        if ($telegramResponse === null) {
            return response()->json(['ok' => false, 'error' => 'Файл не найден или недоступен'], 404);
        }

        $contentType = $telegramResponse->header('Content-Type') ?: 'application/octet-stream';
        $contentLength = $telegramResponse->header('Content-Length');
        $body = $telegramResponse->toPsrResponse()->getBody();

        $headers = ['Content-Type' => $contentType];

        if ($contentLength !== '') {
            $headers['Content-Length'] = $contentLength;
        }

        return response()->stream(function () use ($body): void {
            while (!$body->eof()) {
                echo $body->read(8192);
                flush();
            }
        }, 200, $headers);
    }
}
