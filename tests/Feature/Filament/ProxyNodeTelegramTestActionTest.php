<?php

declare(strict_types=1);

namespace Tests\Feature\Filament;

use App\Enums\BotStatus;
use App\Enums\ProxyNodeStatus;
use App\Filament\Resources\ProxyNodeResource;
use App\Filament\Resources\ProxyNodeResource\Pages\EditProxyNode;
use App\Models\Bot;
use App\Models\ProxyNode;
use App\Models\User;
use App\Services\Proxy\ProxyNodeTelegramTester;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

final class ProxyNodeTelegramTestActionTest extends TestCase
{
    use RefreshDatabase;

    private const string BOT_TOKEN = '123456789:abcdefghijklmnopqrstuvwx';

    protected function setUp(): void
    {
        parent::setUp();

        Http::preventStrayRequests();
        config([
            'botgate.proxy.test_bot_public_id' => 'bot_node_test',
            'botgate.proxy.test_chat_id' => '123',
        ]);
    }

    public function test_admin_action_sends_once_through_the_saved_node_even_when_offline_and_disabled(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $this->actingAs($admin);
        $this->createBot();
        ProxyNode::factory()->create(['priority' => 1]);
        $node = ProxyNode::factory()->create([
            'name' => 'Test node',
            'node_id' => 'proxy-test',
            'url' => 'https://selected.example.test',
            'enabled' => false,
            'status' => ProxyNodeStatus::Offline,
            'priority' => 999,
        ]);
        $attributes = $node->refresh()->getAttributes();
        $this->travelTo(now()->utc()->setDate(2026, 9, 28)->setTime(12, 34, 56));
        Http::fake([
            'https://selected.example.test/v1/telegram/json' => Http::response([
                'ok' => true,
                'result' => ['message_id' => 42],
            ]),
        ]);

        Livewire::test(EditProxyNode::class, ['record' => $node->getRouteKey()])
            ->set('data.url', 'https://unsaved.example.test')
            ->callAction('testTelegram')
            ->assertNotified('Тестовое сообщение отправлено');

        Http::assertSentCount(1);
        Http::assertSent(fn (Request $request): bool => $request->url() === 'https://selected.example.test/v1/telegram/json'
            && $request['bot_token'] === self::BOT_TOKEN
            && $request['method'] === 'sendMessage'
            && $request['params']['chat_id'] === '123'
            && str_contains($request['params']['text'], 'Test node (proxy-test)')
            && str_contains($request['params']['text'], '2026-09-28 12:34:56 UTC'));
        $this->assertSame($attributes, $node->fresh()->getAttributes());
    }

    public function test_action_shows_a_failure_notification_without_sending_when_bot_is_missing(): void
    {
        $this->actingAs(User::factory()->create(['is_admin' => true]));
        $node = ProxyNode::factory()->create();
        Http::fake();

        Livewire::test(EditProxyNode::class, ['record' => $node->getRouteKey()])
            ->callAction('testTelegram')
            ->assertNotified('Тест Telegram не выполнен');

        Http::assertNothingSent();
    }

    public function test_non_admin_cannot_open_the_page_or_execute_the_action(): void
    {
        $this->actingAs(User::factory()->create(['is_admin' => false]));
        $this->createBot();
        $node = ProxyNode::factory()->create();
        Http::fake();

        $this->get(ProxyNodeResource::getUrl('edit', ['record' => $node]))->assertForbidden();
        Livewire::test(EditProxyNode::class, ['record' => $node->getRouteKey()])
            ->assertActionHidden('testTelegram')
            ->call('mountAction', 'testTelegram');

        Http::assertNothingSent();
    }

    public function test_disabled_bot_is_rejected_without_a_request(): void
    {
        $this->createBot(BotStatus::Disabled);
        Http::fake();

        $result = app(ProxyNodeTelegramTester::class)->send(ProxyNode::factory()->create());

        $this->assertFalse($result->successful);
        $this->assertSame('Тестовый бот не найден или отключён.', $result->message);
        Http::assertNothingSent();
    }

    public function test_invalid_configuration_is_rejected_without_a_request(): void
    {
        $this->createBot();
        config(['botgate.proxy.test_chat_id' => 'not-a-chat-id']);
        Http::fake();

        $result = app(ProxyNodeTelegramTester::class)->send(ProxyNode::factory()->create());

        $this->assertFalse($result->successful);
        $this->assertSame('Не настроены тестовый бот или Telegram chat ID.', $result->message);
        Http::assertNothingSent();
    }

    /** @return array<string, array{array<string, mixed>|string, int, string}> */
    public static function failedResponses(): array
    {
        return [
            'telegram error' => [['ok' => false, 'error_code' => 403, 'description' => self::BOT_TOKEN], 403, 'Боту запрещено писать'],
            'telegram error with HTTP 200' => [['ok' => false, 'error_code' => 400], 200, 'Проверьте chat ID'],
            'proxy auth failure' => [['error' => self::BOT_TOKEN], 401, 'HTTP 401'],
            'invalid JSON' => ['<html>'.self::BOT_TOKEN.'</html>', 502, 'некорректный ответ'],
            'unconfirmed success' => [['ok' => true], 200, 'не содержит подтверждения'],
        ];
    }

    #[DataProvider('failedResponses')]
    public function test_failed_responses_are_sanitized_and_not_retried(array|string $payload, int $status, string $expected): void
    {
        $this->createBot();
        $node = ProxyNode::factory()->create();
        Http::fake(['*' => Http::response($payload, $status)]);

        $result = app(ProxyNodeTelegramTester::class)->send($node);

        $this->assertFalse($result->successful);
        $this->assertStringContainsString($expected, $result->message);
        $this->assertStringNotContainsString(self::BOT_TOKEN, $result->message);
        Http::assertSentCount(1);
    }

    public function test_connection_failure_does_not_retry_or_expose_the_exception(): void
    {
        $this->createBot();
        Http::fake(['*' => Http::failedConnection(self::BOT_TOKEN)]);

        $result = app(ProxyNodeTelegramTester::class)->send(ProxyNode::factory()->create());

        $this->assertFalse($result->successful);
        $this->assertStringContainsString('Доставка сообщения неизвестна', $result->message);
        $this->assertStringNotContainsString(self::BOT_TOKEN, $result->message);
        Http::assertSentCount(1);
    }

    private function createBot(BotStatus $status = BotStatus::Active): Bot
    {
        return Bot::query()->create([
            'user_id' => User::factory()->create()->id,
            'public_id' => 'bot_node_test',
            'name' => 'Test Bot',
            'telegram_token_encrypted' => self::BOT_TOKEN,
            'telegram_token_hash' => hash('sha256', self::BOT_TOKEN),
            'status' => $status,
        ]);
    }
}
