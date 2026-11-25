<?php

declare(strict_types=1);

namespace Tests\Feature\Api;

use App\Enums\BotStatus;
use App\Jobs\DeliverWebhookEventJob;
use App\Models\Bot;
use App\Models\User;
use App\Models\WebhookEvent;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

final class WebhookIngressTest extends TestCase
{
    use RefreshDatabase;

    public function test_duplicate_update_creates_one_event_and_one_durable_job(): void
    {
        $bot = $this->bot();
        $url = route('telegram.webhook', [$bot->public_id, $bot->webhook_secret]);
        $payload = ['update_id' => 42, 'message' => ['text' => 'Привет ✓']];
        $this->postJson($url, $payload)->assertOk();
        $this->postJson($url, $payload)->assertOk();
        $this->assertDatabaseCount('webhook_events', 1);
        $this->assertDatabaseCount('jobs', 1);
        $this->assertSame($payload, WebhookEvent::query()->sole()->payload);
        $this->assertDatabaseHas('jobs', ['queue' => 'webhooks']);
    }

    public function test_inactive_deleted_and_delivery_disabled_bots_keep_their_responses(): void
    {
        $bot = $this->bot();
        $url = route('telegram.webhook', [$bot->public_id, $bot->webhook_secret]);
        $bot->update(['webhook_enabled' => false]);
        $this->postJson($url, ['update_id' => 42])->assertOk()->assertExactJson(['ok' => true]);
        $bot->update(['status' => BotStatus::Disabled]);
        $this->postJson($url, ['update_id' => 42])->assertStatus(403)->assertExactJson(['ok' => false]);
        $bot->delete();
        $this->postJson($url, ['update_id' => 42])->assertStatus(403);
        $this->assertDatabaseCount('webhook_events', 0);
        $this->assertDatabaseCount('jobs', 0);
    }

    public function test_updates_without_id_remain_independent_and_ids_are_scoped_to_bot(): void
    {
        foreach ([$this->bot(), $this->bot()] as $bot) {
            $url = route('telegram.webhook', [$bot->public_id, $bot->webhook_secret]);
            $this->postJson($url, ['update_id' => 42])->assertOk();
            $this->postJson($url, ['message' => []])->assertOk();
            $this->postJson($url, ['message' => []])->assertOk();
        }
        $this->assertDatabaseCount('webhook_events', 6);
        $this->assertDatabaseCount('jobs', 6);
    }

    public function test_queue_failure_rolls_back_event_and_retry_can_accept_it(): void
    {
        $bot = $this->bot();
        $url = route('telegram.webhook', [$bot->public_id, $bot->webhook_secret]);
        $queue = Queue::getFacadeRoot();
        Queue::shouldReceive('connection')->andThrow(new \RuntimeException('queue unavailable'));
        $this->postJson($url, ['update_id' => 42])->assertStatus(503)->assertExactJson(['ok' => false]);
        $this->assertDatabaseCount('webhook_events', 0);
        $this->assertDatabaseCount('bot_daily_stats', 0);
        Queue::swap($queue);
        $this->postJson($url, ['update_id' => 42])->assertOk();
        $this->assertDatabaseCount('webhook_events', 1);
        $this->assertDatabaseCount('jobs', 1);
    }

    public function test_historical_duplicates_are_preserved_and_not_queued_again(): void
    {
        $bot = $this->bot();
        foreach ([1, 2] as $unused) {
            WebhookEvent::create(['bot_id' => $bot->id, 'telegram_update_id' => 42,
                'payload' => ['update_id' => 42], 'target_url' => $bot->webhook_url, 'status' => 'sent']);
        }
        $this->postJson(route('telegram.webhook', [$bot->public_id, $bot->webhook_secret]), ['update_id' => 42])->assertOk();
        $this->assertDatabaseCount('webhook_events', 2);
        $this->assertDatabaseCount('jobs', 0);
    }

    public function test_legacy_pending_event_is_recovered_once_and_duplicate_jobs_cannot_deliver_twice(): void
    {
        $bot = $this->bot();
        $event = WebhookEvent::create(['bot_id' => $bot->id, 'telegram_update_id' => 42,
            'payload' => ['update_id' => 42], 'target_url' => $bot->webhook_url, 'status' => 'pending']);
        $url = route('telegram.webhook', [$bot->public_id, $bot->webhook_secret]);
        $this->postJson($url, ['update_id' => 42])->assertOk();
        $this->postJson($url, ['update_id' => 42])->assertOk();
        $this->assertDatabaseCount('jobs', 1);
        $this->assertNotNull($event->fresh()->queued_at);
        Http::fake(function () use ($event) {
            // Interleave another consumer after the first has claimed this event.
            (new DeliverWebhookEventJob($event->id))->handle();
            $legacy = new DeliverWebhookEventJob($event->id);
            (new \ReflectionProperty($legacy, 'expectedAttempts'))->setValue($legacy, null);
            $legacy->handle();

            return Http::response(['ok' => true]);
        });
        (new DeliverWebhookEventJob($event->id))->handle();
        Http::assertSentCount(1);
        $this->assertSame(1, $event->fresh()->attempts);
    }

    public function test_failure_after_job_insert_rolls_back_both_event_and_job(): void
    {
        $bot = $this->bot();
        DB::unprepared("CREATE TRIGGER reject_stat BEFORE INSERT ON bot_daily_stats BEGIN SELECT RAISE(FAIL, 'injected failure'); END");
        $url = route('telegram.webhook', [$bot->public_id, $bot->webhook_secret]);
        $this->postJson($url, ['update_id' => 42])->assertStatus(503);
        $this->assertDatabaseCount('webhook_events', 0);
        $this->assertDatabaseCount('jobs', 0);
        DB::unprepared('DROP TRIGGER reject_stat');
        $this->postJson($url, ['update_id' => 42])->assertOk();
        $this->assertDatabaseCount('jobs', 1);
    }

    private function bot(): Bot
    {
        return Bot::create(['user_id' => User::factory()->create()->id, 'public_id' => 'bot_'.bin2hex(random_bytes(6)),
            'name' => 'Ingress', 'telegram_token_encrypted' => 'test-token',
            'telegram_token_hash' => bin2hex(random_bytes(32)), 'webhook_secret' => 'whsec_test',
            'webhook_url' => 'https://client.example.test/hook', 'webhook_enabled' => true, 'status' => BotStatus::Active]);
    }
}
