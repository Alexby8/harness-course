<?php

declare(strict_types=1);

namespace Tests\Feature\Api;

use App\Enums\BotStatus;
use App\Models\Bot;
use App\Models\ProxyNode;
use App\Models\User;
use App\Services\ApiKeyService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

final class PublicRateLimitCompatibilityTest extends TestCase
{
    use RefreshDatabase;

    private const BOT_TOKEN = '123456789:abcdefghijklmnopqrstuvwx';

    protected function setUp(): void
    {
        parent::setUp();

        Cache::flush();
        ProxyNode::factory()->create(['url' => 'https://proxy.example.test']);
        Http::fake([
            'https://proxy.example.test/v1/telegram/json' => Http::response([
                'ok' => true,
                'result' => ['id' => 123],
            ]),
        ]);
    }

    public function test_api_key_limiter_returns_the_public_429_and_does_not_block_another_key(): void
    {
        config()->set('botgate.rate_limits.api_key_per_minute', 2);
        config()->set('botgate.rate_limits.bot_per_minute', 100);
        [$firstUser, $firstApiKey] = $this->userWithApiKey();
        [$secondUser, $secondApiKey] = $this->userWithApiKey();
        $firstBot = $this->botFor($firstUser, 'bot_first_key');
        $secondBot = $this->botFor($secondUser, 'bot_second_key');

        $this->withToken($firstApiKey)
            ->postJson("/api/v1/bots/{$firstBot->public_id}/getMe")
            ->assertOk()
            ->assertHeader('X-RateLimit-Limit', '2')
            ->assertHeader('X-RateLimit-Remaining', '1');

        $this->withToken($firstApiKey)
            ->postJson("/api/v1/bots/{$firstBot->public_id}/getMe")
            ->assertOk()
            ->assertHeader('X-RateLimit-Remaining', '0');

        $this->withToken($firstApiKey)
            ->postJson("/api/v1/bots/{$firstBot->public_id}/getMe")
            ->assertStatus(429)
            ->assertHeader('Retry-After')
            ->assertExactJson(['ok' => false, 'error' => 'Превышен лимит запросов']);

        $this->withToken($secondApiKey)
            ->postJson("/api/v1/bots/{$secondBot->public_id}/getMe")
            ->assertOk()
            ->assertHeader('X-RateLimit-Remaining', '1');

        Http::assertSentCount(3);
    }

    public function test_bot_limiter_returns_the_public_429_and_does_not_block_another_bot(): void
    {
        config()->set('botgate.rate_limits.api_key_per_minute', 100);
        config()->set('botgate.rate_limits.bot_per_minute', 2);
        [$user, $apiKey] = $this->userWithApiKey();
        $firstBot = $this->botFor($user, 'bot_first_limit');
        $secondBot = $this->botFor($user, 'bot_second_limit');

        $this->withToken($apiKey)
            ->postJson("/api/v1/bots/{$firstBot->public_id}/getMe")
            ->assertOk()
            ->assertHeader('X-RateLimit-Limit', '2')
            ->assertHeader('X-RateLimit-Remaining', '1');

        $this->withToken($apiKey)
            ->postJson("/api/v1/bots/{$firstBot->public_id}/getMe")
            ->assertOk()
            ->assertHeader('X-RateLimit-Remaining', '0');

        $this->withToken($apiKey)
            ->postJson("/api/v1/bots/{$firstBot->public_id}/getMe")
            ->assertStatus(429)
            ->assertHeader('Retry-After')
            ->assertExactJson([
                'ok' => false,
                'error' => 'Превышен лимит запросов для этого бота',
            ]);

        $this->withToken($apiKey)
            ->postJson("/api/v1/bots/{$secondBot->public_id}/getMe")
            ->assertOk()
            ->assertHeader('X-RateLimit-Remaining', '1');

        Http::assertSentCount(3);
    }

    /** @return array{User, string} */
    private function userWithApiKey(): array
    {
        $user = User::factory()->create();
        $apiKey = 'bg_live_'.bin2hex(random_bytes(16));
        $user->update(['api_key_hash' => app(ApiKeyService::class)->hash($apiKey)]);

        return [$user, $apiKey];
    }

    private function botFor(User $user, string $publicId): Bot
    {
        return Bot::query()->create([
            'user_id' => $user->id,
            'public_id' => $publicId,
            'name' => 'Rate Limit Bot',
            'telegram_token_encrypted' => self::BOT_TOKEN,
            'telegram_token_hash' => hash('sha256', self::BOT_TOKEN.$publicId),
            'status' => BotStatus::Active,
        ]);
    }
}
