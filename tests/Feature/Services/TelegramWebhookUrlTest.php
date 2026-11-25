<?php

declare(strict_types=1);

namespace Tests\Feature\Services;

use App\Enums\BotStatus;
use App\Models\Bot;
use App\Models\ProxyNode;
use App\Models\User;
use App\Services\BotService;
use App\Services\TelegramWebhookUrl;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

final class TelegramWebhookUrlTest extends TestCase
{
    use RefreshDatabase;

    public function test_default_url_and_explicit_origin_share_exact_bot_route(): void
    {
        config(['app.url' => 'https://app.example.test', 'botgate.webhook.base_url' => null, 'botgate.webhook.allowed_base_urls' => []]);
        $bot = $this->bot();
        $urls = app(TelegramWebhookUrl::class);
        $old = $urls->forBot($bot);
        $this->assertSame('https://app.example.test/api/v1/telegram/webhook/bot_url/whsec_test', $old);
        config(['botgate.webhook.base_url' => 'https://hooks.example.test/']);
        $new = $urls->forBot($bot);
        $this->assertTrue($urls->isManaged($bot, $old));
        $this->assertTrue($urls->isManaged($bot, $new));
        foreach ([$new.'?x=1', $new.'/extra', str_replace('bot_url', 'bot_other', $new), str_replace('hooks.example.test', 'foreign.test', $new)] as $foreign) {
            $this->assertFalse($urls->isManaged($bot, $foreign));
        }
        config(['botgate.webhook.allowed_base_urls' => ['https://previous.example.test']]);
        $this->assertTrue($urls->isManaged($bot, $urls->forBot($bot, 'https://previous.example.test')));
    }

    public function test_dashboard_recognizes_both_origins_during_transition(): void
    {
        config(['app.url' => 'https://app.example.test', 'botgate.webhook.base_url' => 'https://hooks.example.test', 'botgate.webhook.allowed_base_urls' => []]);
        $bot = $this->bot();
        ProxyNode::factory()->create();
        $sequence = Http::fakeSequence();
        foreach (['https://app.example.test' => 'active', 'https://hooks.example.test' => 'active', 'https://foreign.example.test' => 'conflict'] as $base => $status) {
            $sequence->push(['ok' => true, 'result' => ['url' => app(TelegramWebhookUrl::class)->forBot($bot, $base)]]);
            $this->actingAs($bot->user)->get(route('bots.show', $bot->public_id))->assertOk()->assertViewHas('webhookStatus', $status);
        }
    }

    public function test_registration_uses_config_and_does_not_enable_on_telegram_failure(): void
    {
        config(['botgate.webhook.base_url' => 'https://hooks.example.test']);
        $bot = $this->bot();
        $bot->update(['webhook_enabled' => false]);
        ProxyNode::factory()->create();
        Http::fakeSequence()->push(['ok' => false, 'error_code' => 400, 'description' => 'secret response'])
            ->push(['ok' => true, 'result' => true]);
        try {
            app(BotService::class)->registerTelegramWebhook($bot, $bot->telegram_token_encrypted);
            $this->fail('Registration must reject ok=false');
        } catch (\RuntimeException $e) {
            $this->assertStringNotContainsString('secret response', $e->getMessage());
        }
        $this->assertFalse($bot->fresh()->webhook_enabled);
        app(BotService::class)->registerTelegramWebhook($bot, $bot->telegram_token_encrypted);
        $this->assertTrue($bot->fresh()->webhook_enabled);
        Http::assertSent(fn ($r) => $r['method'] === 'setWebhook' && $r['params']['url'] === app(TelegramWebhookUrl::class)->forBot($bot));
    }

    private function bot(): Bot
    {
        return Bot::create(['user_id' => User::factory()->create()->id, 'public_id' => 'bot_url', 'name' => 'URL',
            'telegram_token_encrypted' => 'token-test', 'telegram_token_hash' => hash('sha256', 'token-test'),
            'status' => BotStatus::Active, 'webhook_secret' => 'whsec_test', 'webhook_enabled' => true, 'webhook_url' => 'https://client.example.test/hook']);
    }
}
