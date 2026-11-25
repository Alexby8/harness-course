<?php

declare(strict_types=1);

namespace Tests\Feature\Services;

use App\Models\ProxyNode;
use App\Services\TelegramService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

final class TelegramServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_get_me_uses_the_selected_proxy_node(): void
    {
        ProxyNode::factory()->create([
            'url' => 'https://proxy.example.test',
            'priority' => 10,
        ]);
        Http::fake([
            'https://proxy.example.test/v1/telegram/json' => Http::response([
                'ok' => true,
                'result' => [
                    'id' => 123,
                    'username' => 'botgate_test_bot',
                ],
            ]),
        ]);

        $result = app(TelegramService::class)->getMe(
            '123456789:abcdefghijklmnopqrstuvwx',
        );

        $this->assertSame(123, $result['id'] ?? null);
        Http::assertSent(fn (Request $request): bool => $request->data()['method'] === 'getMe'
            && $request->data()['params'] === []
        );
    }

    public function test_json_proxy_returns_telegram_body_unchanged(): void
    {
        ProxyNode::factory()->create([
            'url' => 'https://proxy.example.test',
        ]);
        $telegramBody = [
            'ok' => false,
            'error_code' => 400,
            'description' => 'Bad Request: chat not found',
        ];
        Http::fake([
            'https://proxy.example.test/v1/telegram/json' => Http::response($telegramBody, 400),
        ]);

        $result = app(TelegramService::class)->proxyJson(
            '123456789:abcdefghijklmnopqrstuvwx',
            'sendMessage',
            ['chat_id' => 1, 'text' => 'Hello'],
        );

        $this->assertSame($telegramBody, $result);
    }
}
