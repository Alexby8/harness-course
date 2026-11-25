<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Enums\BotStatus;
use App\Http\Controllers\Controller;
use App\Models\ApiRequestLog;
use App\Models\Bot;
use App\Models\BotDailyStat;
use App\Services\TelegramService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

final class TelegramProxyController extends Controller
{
    public function __construct(
        private readonly TelegramService $telegram,
    ) {}

    public function handle(Request $request, string $botPublicId, string $method): JsonResponse
    {
        if (in_array($method, config('botgate.forbidden_methods'), true)) {
            return response()->json([
                'ok' => false,
                'error' => "Метод {$method} не поддерживается",
            ], 403);
        }

        $bot = Bot::where('public_id', $botPublicId)->first();

        if ($bot === null) {
            return response()->json(['ok' => false, 'error' => 'Бот не найден'], 404);
        }

        if ($bot->user_id !== Auth::id()) {
            return response()->json(['ok' => false, 'error' => 'Бот не найден'], 404);
        }

        if ($bot->status !== BotStatus::Active) {
            return response()->json(['ok' => false, 'error' => 'Бот отключён'], 403);
        }

        $token = $bot->telegram_token_encrypted;
        $contentType = str_contains($request->header('Content-Type', ''), 'multipart') ? 'multipart' : 'json';
        $startedAt = microtime(true);

        try {
            $result = $this->proxyRequest($request, $token, $method);
        } catch (\Throwable $e) {
            $this->writeLog($bot, $method, $contentType, null, null, 'Ошибка соединения с Telegram', $startedAt, $request);
            BotDailyStat::incrementStat($bot->id, 'api_requests_count');
            BotDailyStat::incrementStat($bot->id, 'api_errors_count');

            return response()->json(['ok' => false, 'error' => 'Ошибка соединения с Telegram'], 502);
        }

        $telegramOk = (bool) ($result['ok'] ?? false);
        $httpStatus = $telegramOk ? 200 : 400;
        $error = $telegramOk ? null : ($result['description'] ?? null);

        $this->writeLog($bot, $method, $contentType, $httpStatus, $telegramOk, $error, $startedAt, $request);
        BotDailyStat::incrementStat($bot->id, 'api_requests_count');

        if (! $telegramOk) {
            BotDailyStat::incrementStat($bot->id, 'api_errors_count');
        } else {
            BotDailyStat::recordWorkApiRequest($bot->id, $method);
        }

        return response()->json($result, $httpStatus);
    }

    private function writeLog(
        Bot $bot,
        string $method,
        string $contentType,
        ?int $httpStatus,
        ?bool $telegramOk,
        ?string $error,
        float $startedAt,
        Request $request,
    ): void {
        ApiRequestLog::create([
            'user_id' => $bot->user_id,
            'bot_id' => $bot->id,
            'method' => $method,
            'content_type' => $contentType,
            'telegram_http_status' => $httpStatus,
            'telegram_ok' => $telegramOk,
            'error' => $error,
            'duration_ms' => (int) round((microtime(true) - $startedAt) * 1000),
            'ip_address' => $request->ip(),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function proxyRequest(Request $request, string $token, string $method): array
    {
        $contentType = $request->header('Content-Type', '');

        if (str_contains($contentType, 'multipart/form-data')) {
            return $this->proxyMultipart($request, $token, $method);
        }

        return $this->telegram->proxyJson($token, $method, $request->all());
    }

    /**
     * @return array<string, mixed>
     */
    private function proxyMultipart(Request $request, string $token, string $method): array
    {
        $fields = $request->except($request->files->keys());
        $files = [];

        foreach ($request->allFiles() as $fieldName => $uploadedFile) {
            $files[$fieldName] = [
                'path' => $uploadedFile->getRealPath(),
                'filename' => $uploadedFile->getClientOriginalName(),
                'mime_type' => $uploadedFile->getClientMimeType(),
            ];
        }

        return $this->telegram->proxyMultipart($token, $method, $fields, $files);
    }
}
