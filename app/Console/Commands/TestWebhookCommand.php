<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Enums\WebhookEventStatus;
use App\Jobs\DeliverWebhookEventJob;
use App\Models\Bot;
use App\Models\WebhookEvent;
use Illuminate\Console\Command;

final class TestWebhookCommand extends Command
{
    protected $signature = 'botgate:test-webhook
        {bot_public_id : Public ID бота}
        {--queued : Поставить событие в очередь webhooks вместо синхронной доставки}';

    protected $description = 'Отправляет тестовое событие на webhook URL бота';

    public function handle(): int
    {
        $publicId = $this->argument('bot_public_id');

        if ($publicId === '') {
            $this->error('Некорректный public ID бота.');

            return self::INVALID;
        }

        $bot = Bot::where('public_id', $publicId)->first();

        if ($bot === null) {
            $this->error("Бот с ID {$publicId} не найден.");

            return Command::FAILURE;
        }

        if (empty($bot->webhook_url)) {
            $this->error('У бота не задан webhook URL.');

            return Command::FAILURE;
        }

        $targetHost = parse_url($bot->webhook_url, PHP_URL_HOST);
        $this->info('Webhook target: '.(is_string($targetHost) ? $targetHost : 'настроенный URL'));

        $event = WebhookEvent::create([
            'bot_id' => $bot->id,
            'telegram_update_id' => null,
            'payload' => [
                'botgate_test' => true,
                'bot_public_id' => $bot->public_id,
                'message' => 'Тестовое событие от BotGate',
                'timestamp' => now()->toIso8601String(),
            ],
            'target_url' => $bot->webhook_url,
            'status' => WebhookEventStatus::Pending,
        ]);

        if ($this->option('queued')) {
            DeliverWebhookEventJob::dispatch($event->id)->onConnection('webhook_ingress');
            $this->info("Событие #{$event->id} поставлено в очередь webhooks.");

            return Command::SUCCESS;
        }

        DeliverWebhookEventJob::dispatchSync($event->id);

        $event->refresh();

        if ($event->status === WebhookEventStatus::Sent) {
            $this->info('Успешно доставлено!');

            return Command::SUCCESS;
        }

        $this->error("Ошибка доставки: {$event->last_error}");

        return Command::FAILURE;
    }
}
