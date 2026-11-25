<?php

declare(strict_types=1);

namespace Tests\Feature\Console;

use App\Enums\BotStatus;
use App\Models\Bot;
use App\Models\ProxyNode;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

final class CallTelegramMethodCommandTest extends TestCase
{
    use RefreshDatabase;

    private const BOT_TOKEN = '123456789:abcdefghijklmnopqrstuvwx';

    public function test_it_sends_an_arbitrary_json_method_through_a_proxy_node(): void
    {
        $bot = $this->createBot();
        ProxyNode::factory()->create(['url' => 'https://proxy.example.test']);
        Http::fake([
            'https://proxy.example.test/v1/telegram/json' => Http::response([
                'ok' => true,
                'result' => ['message_id' => 42],
            ]),
        ]);

        $this->artisan('botgate:telegram:call', [
            'bot_public_id' => $bot->public_id,
            'method' => 'sendMessage',
            '--params' => '{"chat_id":123,"text":"Привет"}',
            '--force' => true,
        ])->assertSuccessful();

        Http::assertSent(fn (Request $request): bool => $request->url() === 'https://proxy.example.test/v1/telegram/json'
            && $request->data()['method'] === 'sendMessage'
            && $request->data()['params'] === ['chat_id' => 123, 'text' => 'Привет']);
    }

    public function test_params_default_to_an_empty_object(): void
    {
        $bot = $this->createBot();
        ProxyNode::factory()->create(['url' => 'https://proxy.example.test']);
        Http::fake([
            'https://proxy.example.test/v1/telegram/json' => Http::response([
                'ok' => true,
                'result' => ['id' => 123],
            ]),
        ]);

        $this->artisan('botgate:telegram:call', [
            'bot_public_id' => $bot->public_id,
            'method' => 'getMe',
            '--force' => true,
        ])->assertSuccessful();

        Http::assertSent(fn (Request $request): bool => $request->data()['method'] === 'getMe'
            && $request->data()['params'] === []);
    }

    public function test_it_sends_a_file_as_multipart(): void
    {
        $bot = $this->createBot();
        ProxyNode::factory()->create(['url' => 'https://proxy.example.test']);
        $photo = UploadedFile::fake()->image('smoke-photo.jpg');
        Http::fake([
            'https://proxy.example.test/v1/telegram/multipart' => Http::response([
                'ok' => true,
                'result' => ['message_id' => 43],
            ]),
        ]);

        $this->artisan('botgate:telegram:call', [
            'bot_public_id' => $bot->public_id,
            'method' => 'sendPhoto',
            '--params' => '{"chat_id":123,"caption":"Smoke"}',
            '--file' => ['photo='.$photo->getRealPath()],
            '--force' => true,
        ])->assertSuccessful();

        Http::assertSent(fn (Request $request): bool => $request->url() === 'https://proxy.example.test/v1/telegram/multipart'
            && $request->hasHeader('X-BotGate-Telegram-Method', 'sendPhoto')
            && str_contains($request->body(), 'name="photo"')
            && str_contains($request->body(), 'name="caption"')
            && str_contains($request->body(), 'Smoke'));
    }

    public function test_it_does_not_call_telegram_when_confirmation_is_declined(): void
    {
        $bot = $this->createBot();
        ProxyNode::factory()->create();
        Http::fake();

        $this->artisan('botgate:telegram:call', [
            'bot_public_id' => $bot->public_id,
            'method' => 'sendMessage',
            '--params' => '{"chat_id":123,"text":"Не отправлять"}',
        ])->expectsConfirmation('Выполнить реальный запрос к Telegram?', 'no')
            ->assertSuccessful();

        Http::assertNothingSent();
    }

    public function test_it_rejects_forbidden_methods_and_invalid_params(): void
    {
        $bot = $this->createBot();
        Http::fake();

        $this->artisan('botgate:telegram:call', [
            'bot_public_id' => $bot->public_id,
            'method' => 'getUpdates',
            '--force' => true,
        ])->assertFailed();

        $this->artisan('botgate:telegram:call', [
            'bot_public_id' => $bot->public_id,
            'method' => 'sendMessage',
            '--params' => '[1,2,3]',
            '--force' => true,
        ])->assertExitCode(2);

        Http::assertNothingSent();
    }

    public function test_it_rejects_a_disabled_bot(): void
    {
        $bot = $this->createBot(BotStatus::Disabled);
        Http::fake();

        $this->artisan('botgate:telegram:call', [
            'bot_public_id' => $bot->public_id,
            'method' => 'getMe',
            '--force' => true,
        ])->assertFailed();

        Http::assertNothingSent();
    }

    private function createBot(BotStatus $status = BotStatus::Active): Bot
    {
        $user = User::factory()->create();

        return Bot::query()->create([
            'user_id' => $user->id,
            'public_id' => 'bot_smoke_test',
            'name' => 'Smoke Test Bot',
            'telegram_token_encrypted' => self::BOT_TOKEN,
            'telegram_token_hash' => hash('sha256', self::BOT_TOKEN),
            'status' => $status,
        ]);
    }
}
