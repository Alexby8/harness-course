<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\BotStatus;
use App\Models\Bot;
use App\Models\User;
use Illuminate\Support\Str;

final class BotService
{
    public function __construct(
        private readonly TelegramService $telegram,
        private readonly TelegramWebhookUrl $webhookUrls,
    ) {}

    /**
     * Validate token via Telegram getMe and create a bot.
     *
     * @param  array<string, mixed>  $data  name, token
     * @return array{bot: Bot, api_info: array<string, mixed>}
     *
     * @throws \RuntimeException if token is invalid or already used
     */
    public function create(User $user, array $data): array
    {
        $token = $data['token'];
        $tokenHash = hash('sha256', $token);

        if (Bot::where('telegram_token_hash', $tokenHash)->exists()) {
            throw new \RuntimeException('Этот Telegram Bot Token уже зарегистрирован в системе.');
        }

        $botInfo = $this->telegram->getMe($token);

        if ($botInfo === null) {
            throw new \RuntimeException('Неверный Telegram Bot Token. Проверьте токен и попробуйте снова.');
        }

        $bot = $user->bots()->create([
            'public_id' => $this->generatePublicId(),
            'name' => $data['name'],
            'telegram_token_encrypted' => $token,
            'telegram_token_hash' => $tokenHash,
            'telegram_bot_id' => (string) ($botInfo['id'] ?? ''),
            'telegram_username' => $botInfo['username'] ?? null,
            'telegram_first_name' => $botInfo['first_name'] ?? null,
            'webhook_secret' => $this->generateWebhookSecret(),
            'status' => BotStatus::Active,
        ]);

        return ['bot' => $bot, 'api_info' => $botInfo];
    }

    /**
     * Update bot name and optionally replace token.
     *
     * @param  array<string, mixed>  $data  name, token (nullable)
     *
     * @throws \RuntimeException if token is invalid or already used
     */
    public function update(Bot $bot, array $data): void
    {
        $fields = ['name' => $data['name']];

        if (! empty($data['token'])) {
            $token = $data['token'];
            $tokenHash = hash('sha256', $token);

            if ($tokenHash !== $bot->telegram_token_hash) {
                if (Bot::where('telegram_token_hash', $tokenHash)->where('id', '!=', $bot->id)->exists()) {
                    throw new \RuntimeException('Этот Telegram Bot Token уже зарегистрирован в системе.');
                }

                $botInfo = $this->telegram->getMe($token);

                if ($botInfo === null) {
                    throw new \RuntimeException('Неверный Telegram Bot Token. Проверьте токен и попробуйте снова.');
                }

                $fields['telegram_token_encrypted'] = $token;
                $fields['telegram_token_hash'] = $tokenHash;
                $fields['telegram_bot_id'] = (string) ($botInfo['id'] ?? '');
                $fields['telegram_username'] = $botInfo['username'] ?? null;
                $fields['telegram_first_name'] = $botInfo['first_name'] ?? null;
            }
        }

        $bot->update($fields);
    }

    /**
     * Set delivery URL and register BotGate as webhook in Telegram.
     *
     * @throws \RuntimeException
     */
    public function activateWebhook(Bot $bot, string $webhookUrl): void
    {
        $bot->update(['webhook_url' => $webhookUrl]);
        $this->registerTelegramWebhook($bot, $bot->telegram_token_encrypted);
    }

    /**
     * Clear delivery URL and remove webhook from Telegram.
     */
    public function deactivateWebhook(Bot $bot): void
    {
        $this->telegram->proxyJson($bot->telegram_token_encrypted, 'deleteWebhook', []);
        $bot->update(['webhook_url' => null, 'webhook_enabled' => false]);
    }

    public function registerTelegramWebhook(Bot $bot, string $token): void
    {
        try {
            $response = $this->telegram->proxyJson($token, 'setWebhook', ['url' => $this->webhookUrls->forBot($bot)]);
        } catch (\Throwable) {
            throw new \RuntimeException('Не удалось подтвердить регистрацию webhook. Проверьте статус в Telegram перед повтором.');
        }
        if (($response['ok'] ?? null) !== true || ($response['result'] ?? null) !== true) {
            throw new \RuntimeException('Telegram не подтвердил регистрацию webhook.');
        }
        $bot->update(['webhook_enabled' => true]);
    }

    public function disable(Bot $bot): void
    {
        $bot->update(['status' => BotStatus::Disabled]);
    }

    public function enable(Bot $bot): void
    {
        $bot->update(['status' => BotStatus::Active]);
    }

    private function generatePublicId(): string
    {
        do {
            $id = 'bot_'.Str::random(12);
        } while (Bot::withTrashed()->where('public_id', $id)->exists());

        return $id;
    }

    private function generateWebhookSecret(): string
    {
        return 'whsec_'.bin2hex(random_bytes(16));
    }
}
