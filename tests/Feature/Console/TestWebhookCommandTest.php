<?php

declare(strict_types=1);

namespace Tests\Feature\Console;

use App\Enums\BotStatus;
use App\Enums\WebhookEventStatus;
use App\Jobs\DeliverWebhookEventJob;
use App\Models\Bot;
use App\Models\User;
use App\Models\WebhookEvent;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

final class TestWebhookCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_can_queue_a_test_event_on_the_webhooks_queue(): void
    {
        config(['queue.default' => 'redis']);
        Queue::fake();
        $bot = $this->createBot();

        $this->artisan('botgate:test-webhook', [
            'bot_public_id' => $bot->public_id,
            '--queued' => true,
        ])->assertSuccessful();

        $event = WebhookEvent::query()->sole();
        $this->assertSame(WebhookEventStatus::Pending, $event->status);
        $this->assertSame('https://client.example.test/secret-callback', $event->target_url);
        Queue::assertPushedOn('webhooks', DeliverWebhookEventJob::class,
            fn (DeliverWebhookEventJob $job): bool => $job->connection === 'webhook_ingress');
    }

    public function test_it_can_deliver_a_test_event_synchronously(): void
    {
        $bot = $this->createBot();
        Http::fake([
            'https://client.example.test/secret-callback' => Http::response(['ok' => true]),
        ]);

        $this->artisan('botgate:test-webhook', [
            'bot_public_id' => $bot->public_id,
        ])->assertSuccessful();

        $event = WebhookEvent::query()->sole();
        $this->assertSame(WebhookEventStatus::Sent, $event->status);
        $this->assertSame(1, $event->attempts);
    }

    private function createBot(): Bot
    {
        return Bot::query()->create([
            'user_id' => User::factory()->create()->id,
            'public_id' => 'bot_webhook_command',
            'name' => 'Webhook Command Bot',
            'telegram_token_encrypted' => '123456789:abcdefghijklmnopqrstuvwx',
            'telegram_token_hash' => hash('sha256', 'webhook-command-token'),
            'webhook_url' => 'https://client.example.test/secret-callback',
            'webhook_secret' => 'webhook-signing-secret',
            'webhook_enabled' => true,
            'status' => BotStatus::Active,
        ]);
    }
}
