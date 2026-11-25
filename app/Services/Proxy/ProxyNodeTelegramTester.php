<?php

declare(strict_types=1);

namespace App\Services\Proxy;

use App\Data\ProxyNodeTelegramTestResult;
use App\Models\Bot;
use App\Models\ProxyNode;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Log;
use Throwable;

final readonly class ProxyNodeTelegramTester
{
    public function __construct(private ProxyNodeClient $client) {}

    public function send(ProxyNode $node): ProxyNodeTelegramTestResult
    {
        $publicId = config('botgate.proxy.test_bot_public_id');
        $chatId = config('botgate.proxy.test_chat_id');

        if (! is_string($publicId) || $publicId === '' || ! is_string($chatId) || ! preg_match('/^-?[1-9][0-9]*$/D', $chatId)) {
            return new ProxyNodeTelegramTestResult(false, 'Не настроены тестовый бот или Telegram chat ID.');
        }

        $bot = Bot::query()->where('public_id', $publicId)->first();

        if ($bot === null || ! $bot->isActive()) {
            return new ProxyNodeTelegramTestResult(false, 'Тестовый бот не найден или отключён.');
        }

        $startedAt = hrtime(true);

        try {
            $response = $this->client->sendJson($node, $bot->telegram_token_encrypted, 'sendMessage', [
                'chat_id' => $chatId,
                'text' => "BotGate: тест Telegram\nНода: {$node->name} ({$node->node_id})\nВремя: ".now()->utc()->format('Y-m-d H:i:s').' UTC',
            ]);
        } catch (Throwable $exception) {
            Log::warning('Proxy node Telegram test failed', [
                'node_id' => $node->node_id,
                'exception_type' => $exception::class,
            ]);

            return new ProxyNodeTelegramTestResult(false, $exception instanceof ConnectionException
                ? 'Нет ответа от ноды: ошибка соединения или таймаут. Доставка сообщения неизвестна; автоматического повтора нет.'
                : 'Не удалось выполнить тест. Проверьте настройки ноды и тестового бота.');
        }

        $payload = $response->json();

        if (! is_array($payload)) {
            return new ProxyNodeTelegramTestResult(false, 'Нода вернула некорректный ответ (HTTP '.$response->status().').');
        }

        if (! $response->successful() || ($payload['ok'] ?? null) !== true) {
            $errorCode = $payload['error_code'] ?? null;
            $reason = match ($errorCode) {
                400 => 'Проверьте chat ID и доступность чата для тестового бота.',
                401 => 'Проверьте токен тестового бота.',
                403 => 'Боту запрещено писать в чат. Проверьте, что он запущен и не заблокирован.',
                429 => 'Превышен лимит запросов. Повторите тест позднее.',
                default => 'Проверьте доступ ноды к Telegram и настройки тестового бота.',
            };

            return new ProxyNodeTelegramTestResult(false, 'Ошибка запроса (HTTP '.$response->status()
                .(is_int($errorCode) ? ', код '.$errorCode : '').'). '.$reason);
        }

        if (! is_array($payload['result'] ?? null) || ! is_int($payload['result']['message_id'] ?? null)) {
            return new ProxyNodeTelegramTestResult(false, 'Ответ не содержит подтверждения отправки сообщения.');
        }

        $durationMs = (int) round((hrtime(true) - $startedAt) / 1_000_000);

        return new ProxyNodeTelegramTestResult(true, "Telegram подтвердил отправку в чат {$chatId}. Ответ за {$durationMs} мс.");
    }
}
