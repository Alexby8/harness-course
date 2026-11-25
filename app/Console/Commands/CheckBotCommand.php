<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\Bot;
use App\Services\TelegramService;
use Illuminate\Console\Command;

class CheckBotCommand extends Command
{
    protected $signature = 'botgate:check-bot {bot_public_id}';
    protected $description = 'Проверяет токен бота через Telegram getMe';

    public function __construct(
        private readonly TelegramService $telegram,
    ) {
        parent::__construct();
    }

    public function handle(): int
    {
        $publicId = $this->argument('bot_public_id');
        $bot = Bot::where('public_id', $publicId)->first();

        if ($bot === null) {
            $this->error("Бот с ID {$publicId} не найден.");
            return Command::FAILURE;
        }

        $this->info("Проверяем бота: {$bot->name} ({$bot->public_id})");

        $token = $bot->telegram_token_encrypted;
        $result = $this->telegram->getMe($token);

        if ($result === null) {
            $this->error('Токен недействителен или Telegram недоступен.');
            return Command::FAILURE;
        }

        $this->info('Токен действителен. Данные от Telegram:');
        $this->table(
            ['Поле', 'Значение'],
            [
                ['ID', $result['id'] ?? '—'],
                ['Имя', $result['first_name'] ?? '—'],
                ['Username', '@' . ($result['username'] ?? '—')],
                ['Is bot', $result['is_bot'] ? 'Да' : 'Нет'],
            ]
        );

        return Command::SUCCESS;
    }
}
