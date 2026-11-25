<?php

declare(strict_types=1);

namespace Tests\Feature\Jobs;

use App\Enums\BotStatus;
use App\Enums\WebhookEventStatus;
use App\Jobs\DeliverWebhookEventJob;
use App\Models\Bot;
use App\Models\BotDailyStat;
use App\Models\User;
use App\Models\WebhookEvent;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

final class DeliverWebhookEventJobTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        Date::setTestNow();

        parent::tearDown();
    }

    public function test_successful_delivery_preserves_payload_and_adds_hmac_headers(): void
    {
        Date::setTestNow('2026-08-30 20:30:00');
        $bot = $this->createBot();
        $payload = [
            'update_id' => 123456,
            'message' => ['text' => 'Привет ✓', 'chat' => ['id' => 42]],
        ];
        $event = $this->createEvent($bot, $payload);
        Http::fake([
            'https://client.example.test/webhook' => Http::response(['ok' => true]),
        ]);

        (new DeliverWebhookEventJob($event->id))->handle();

        $event->refresh();
        $bot->refresh();
        $this->assertSame(WebhookEventStatus::Sent, $event->status);
        $this->assertSame(1, $event->attempts);
        $this->assertSame(200, $event->last_http_status);
        $this->assertNull($event->last_error);
        $this->assertNotNull($event->delivered_at);
        $this->assertNotNull($bot->last_webhook_delivery_at);
        $this->assertNull($bot->last_webhook_error);
        $this->assertSame(1, BotDailyStat::query()->sole()->webhook_sent_count);

        Http::assertSent(function (Request $request) use ($bot, $event, $payload): bool {
            $body = json_encode($payload);
            $signature = hash_hmac('sha256', $body, (string) $bot->webhook_secret);

            return $request->url() === 'https://client.example.test/webhook'
                && $request->method() === 'POST'
                && $request->body() === $body
                && $request->hasHeader('User-Agent', 'BotGate Webhook')
                && $request->hasHeader('X-BotGate-Bot-Id', $bot->public_id)
                && $request->hasHeader('X-BotGate-Event-Id', (string) $event->id)
                && $request->hasHeader('X-BotGate-Signature', $signature);
        });
    }

    public function test_first_failure_schedules_a_retry_after_sixty_seconds(): void
    {
        Date::setTestNow('2026-08-30 20:30:00');
        Queue::fake();
        $bot = $this->createBot();
        $event = $this->createEvent($bot, ['update_id' => 1]);
        Http::fake([
            'https://client.example.test/webhook' => Http::response('temporary error', 503),
        ]);

        (new DeliverWebhookEventJob($event->id))->handle();

        $event->refresh();
        $this->assertSame(WebhookEventStatus::Processing, $event->status);
        $this->assertSame(1, $event->attempts);
        $this->assertSame(503, $event->last_http_status);
        $this->assertSame('HTTP 503', $event->last_error);
        Queue::assertPushed(
            DeliverWebhookEventJob::class,
            fn (DeliverWebhookEventJob $job): bool => $job->delay?->equalTo(now()->addSeconds(60)) === true,
        );
    }

    public function test_second_failure_schedules_a_retry_after_three_hundred_seconds(): void
    {
        Date::setTestNow('2026-08-30 20:30:00');
        Queue::fake();
        $bot = $this->createBot();
        $event = $this->createEvent($bot, ['update_id' => 2], attempts: 1);
        Http::fake([
            'https://client.example.test/webhook' => Http::failedConnection('offline'),
        ]);

        (new DeliverWebhookEventJob($event->id, 1))->handle();

        $event->refresh();
        $this->assertSame(WebhookEventStatus::Processing, $event->status);
        $this->assertSame(2, $event->attempts);
        $this->assertNull($event->last_http_status);
        $this->assertNotNull($event->last_error);
        Queue::assertPushed(
            DeliverWebhookEventJob::class,
            fn (DeliverWebhookEventJob $job): bool => $job->delay?->equalTo(now()->addSeconds(300)) === true,
        );
    }

    public function test_third_failure_marks_the_event_failed_without_another_retry(): void
    {
        Queue::fake();
        $bot = $this->createBot();
        $event = $this->createEvent($bot, ['update_id' => 3], attempts: 2);
        Http::fake([
            'https://client.example.test/webhook' => Http::response('permanent error', 500),
        ]);

        (new DeliverWebhookEventJob($event->id, 2))->handle();

        $event->refresh();
        $bot->refresh();
        $this->assertSame(WebhookEventStatus::Failed, $event->status);
        $this->assertSame(3, $event->attempts);
        $this->assertSame(500, $event->last_http_status);
        $this->assertSame('HTTP 500', $event->last_error);
        $this->assertSame('HTTP 500', $bot->last_webhook_error);
        $this->assertSame(1, BotDailyStat::query()->sole()->webhook_failed_count);
        Queue::assertNothingPushed();
    }

    public function test_an_already_sent_event_is_idempotent(): void
    {
        Queue::fake();
        $bot = $this->createBot();
        $event = $this->createEvent(
            $bot,
            ['update_id' => 4],
            status: WebhookEventStatus::Sent,
            attempts: 1,
        );
        Http::fake();

        (new DeliverWebhookEventJob($event->id))->handle();

        $this->assertSame(1, $event->fresh()?->attempts);
        Http::assertNothingSent();
        Queue::assertNothingPushed();
    }

    public function test_a_due_retry_serialized_before_upgrade_still_delivers(): void
    {
        $bot = $this->createBot();
        $event = $this->createEvent($bot, ['update_id' => 5], status: WebhookEventStatus::Processing, attempts: 1);
        $event->update(['last_attempt_at' => now()->subSeconds(61)]);
        Http::fake(['*' => Http::response(['ok' => true])]);
        $legacy = new DeliverWebhookEventJob($event->id);
        (new \ReflectionProperty($legacy, 'expectedAttempts'))->setValue($legacy, null);
        $legacy->handle();
        Http::assertSentCount(1);
        $this->assertSame(2, $event->fresh()->attempts);
        $this->assertSame(WebhookEventStatus::Sent, $event->fresh()->status);
    }

    private function createBot(): Bot
    {
        return Bot::query()->create([
            'user_id' => User::factory()->create()->id,
            'public_id' => 'bot_webhook_delivery',
            'name' => 'Webhook Delivery Bot',
            'telegram_token_encrypted' => '123456789:abcdefghijklmnopqrstuvwx',
            'telegram_token_hash' => hash('sha256', 'webhook-delivery-token'),
            'webhook_url' => 'https://client.example.test/webhook',
            'webhook_secret' => 'webhook-signing-secret',
            'webhook_enabled' => true,
            'status' => BotStatus::Active,
        ]);
    }

    /** @param array<string, mixed> $payload */
    private function createEvent(
        Bot $bot,
        array $payload,
        WebhookEventStatus $status = WebhookEventStatus::Pending,
        int $attempts = 0,
    ): WebhookEvent {
        return WebhookEvent::query()->create([
            'bot_id' => $bot->id,
            'telegram_update_id' => $payload['update_id'] ?? null,
            'payload' => $payload,
            'target_url' => 'https://client.example.test/webhook',
            'status' => $status,
            'attempts' => $attempts,
        ]);
    }
}
