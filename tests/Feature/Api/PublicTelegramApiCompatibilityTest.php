<?php

declare(strict_types=1);

namespace Tests\Feature\Api;

use App\Enums\BotStatus;
use App\Jobs\DeliverWebhookEventJob;
use App\Models\Bot;
use App\Models\BotDailyStat;
use App\Models\ProxyNode;
use App\Models\User;
use App\Models\WebhookEvent;
use App\Services\ApiKeyService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

final class PublicTelegramApiCompatibilityTest extends TestCase
{
    use RefreshDatabase;

    private const BOT_TOKEN = '123456789:abcdefghijklmnopqrstuvwx';

    public function test_check_and_work_methods_keep_their_public_responses(): void
    {
        [$user, $apiKey] = $this->userWithApiKey();
        $bot = $this->botFor($user);
        ProxyNode::factory()->create(['url' => 'https://proxy.example.test']);
        $body = ['ok' => true, 'result' => ['id' => 42]];
        Http::fake(['https://proxy.example.test/*' => Http::response($body)]);

        foreach (['getMe', 'getWebhookInfo', 'sendMessage'] as $method) {
            $this->withToken($apiKey)->postJson("/api/v1/bots/{$bot->public_id}/{$method}")
                ->assertOk()->assertExactJson($body);
        }
        $this->assertSame(3, BotDailyStat::query()->sole()->api_requests_count);
        $this->assertSame(1, BotDailyStat::query()->sole()->work_api_requests_count);
    }

    public function test_only_successful_work_requests_increment_usage_and_legacy_days_stay_uncertain(): void
    {
        [$user, $apiKey] = $this->userWithApiKey();
        $bot = $this->botFor($user);
        ProxyNode::factory()->create(['url' => 'https://proxy.example.test']);
        Http::fake(['https://proxy.example.test/*' => Http::sequence()
            ->push(['ok' => true, 'result' => []])
            ->push(['ok' => false, 'description' => 'Bad Request'], 400)
            ->push(['ok' => true, 'result' => []])
            ->push(['ok' => true, 'result' => []])]);

        $url = "/api/v1/bots/{$bot->public_id}/";
        $this->withToken($apiKey)->postJson($url.'getMe')->assertOk();
        $this->assertSame(0, BotDailyStat::query()->sole()->work_api_requests_count);
        $this->withToken($apiKey)->postJson($url.'sendMessage')->assertStatus(400);
        $this->assertSame(0, BotDailyStat::query()->sole()->work_api_requests_count);
        DB::table('bot_daily_stats')->update(['work_api_requests_count' => null]);
        $this->withToken($apiKey)->postJson($url.'getWebhookInfo')->assertOk();
        $this->assertNull(BotDailyStat::query()->sole()->work_api_requests_count);
        $this->withToken($apiKey)->postJson($url.'sendMessage')->assertOk();
        $this->assertSame(1, BotDailyStat::query()->sole()->work_api_requests_count);
    }

    public function test_proxy_endpoint_requires_a_bearer_api_key(): void
    {
        $this->postJson('/api/v1/bots/bot_missing/sendMessage', ['chat_id' => 1])
            ->assertStatus(401)
            ->assertExactJson(['ok' => false, 'error' => 'API ключ не передан']);

        $this->withToken('invalid-key')
            ->postJson('/api/v1/bots/bot_missing/sendMessage', ['chat_id' => 1])
            ->assertStatus(401)
            ->assertExactJson(['ok' => false, 'error' => 'Неверный API ключ']);
    }

    public function test_json_proxy_preserves_the_telegram_response(): void
    {
        [$user, $apiKey] = $this->userWithApiKey();
        $bot = $this->botFor($user);
        ProxyNode::factory()->create(['url' => 'https://proxy.example.test']);
        $telegramResponse = [
            'ok' => true,
            'result' => [
                'message_id' => 42,
                'text' => 'Привет, мир!',
            ],
        ];
        Http::fake([
            'https://proxy.example.test/v1/telegram/json' => Http::response($telegramResponse),
        ]);

        $this->withToken($apiKey)
            ->postJson("/api/v1/bots/{$bot->public_id}/sendMessage", [
                'chat_id' => 123,
                'text' => 'Привет, мир!',
                'disable_notification' => false,
            ])
            ->assertOk()
            ->assertExactJson($telegramResponse);

        Http::assertSent(fn (Request $request): bool => $request->data()['method'] === 'sendMessage'
            && $request->data()['params'] === [
                'chat_id' => 123,
                'text' => 'Привет, мир!',
                'disable_notification' => false,
            ]);
    }

    public function test_telegram_error_body_is_returned_with_the_existing_public_status(): void
    {
        [$user, $apiKey] = $this->userWithApiKey();
        $bot = $this->botFor($user);
        ProxyNode::factory()->create(['url' => 'https://proxy.example.test']);
        $telegramResponse = [
            'ok' => false,
            'error_code' => 400,
            'description' => 'Bad Request: chat not found',
        ];
        Http::fake([
            'https://proxy.example.test/v1/telegram/json' => Http::response($telegramResponse, 400),
        ]);

        $this->withToken($apiKey)
            ->postJson("/api/v1/bots/{$bot->public_id}/sendMessage", ['chat_id' => 999])
            ->assertStatus(400)
            ->assertExactJson($telegramResponse);
    }

    public function test_proxy_connection_error_keeps_the_public_502_response(): void
    {
        [$user, $apiKey] = $this->userWithApiKey();
        $bot = $this->botFor($user);
        ProxyNode::factory()->create(['url' => 'https://proxy.example.test']);
        Http::fake([
            'https://proxy.example.test/*' => Http::failedConnection('offline'),
        ]);

        $this->withToken($apiKey)
            ->postJson("/api/v1/bots/{$bot->public_id}/sendMessage", ['chat_id' => 123])
            ->assertStatus(502)
            ->assertExactJson(['ok' => false, 'error' => 'Ошибка соединения с Telegram']);
    }

    public function test_foreign_and_disabled_bots_keep_their_public_errors(): void
    {
        [$owner] = $this->userWithApiKey();
        [, $otherApiKey] = $this->userWithApiKey();
        $foreignBot = $this->botFor($owner, 'bot_foreign');
        $disabledBot = $this->botFor($owner, 'bot_disabled', BotStatus::Disabled);

        $this->withToken($otherApiKey)
            ->postJson("/api/v1/bots/{$foreignBot->public_id}/getMe")
            ->assertStatus(404)
            ->assertExactJson(['ok' => false, 'error' => 'Бот не найден']);

        [, $ownerApiKey] = $this->attachApiKey($owner);
        $this->withToken($ownerApiKey)
            ->postJson("/api/v1/bots/{$disabledBot->public_id}/getMe")
            ->assertStatus(403)
            ->assertExactJson(['ok' => false, 'error' => 'Бот отключён']);
    }

    public function test_get_updates_remains_forbidden(): void
    {
        [$user, $apiKey] = $this->userWithApiKey();
        $bot = $this->botFor($user);

        $this->withToken($apiKey)
            ->postJson("/api/v1/bots/{$bot->public_id}/getUpdates")
            ->assertStatus(403)
            ->assertExactJson(['ok' => false, 'error' => 'Метод getUpdates не поддерживается']);
    }

    public function test_documented_multipart_upload_preserves_fields_file_and_response(): void
    {
        [$user, $apiKey] = $this->userWithApiKey();
        $bot = $this->botFor($user);
        ProxyNode::factory()->create(['url' => 'https://proxy.example.test']);
        $telegramResponse = ['ok' => true, 'result' => ['message_id' => 43]];
        Http::fake([
            'https://proxy.example.test/v1/telegram/multipart' => Http::response($telegramResponse),
        ]);

        $this->withToken($apiKey)
            ->withHeader('Content-Type', 'multipart/form-data; boundary=testing')
            ->post("/api/v1/bots/{$bot->public_id}/sendPhoto", [
                'chat_id' => '123456789',
                'caption' => 'Подпись с Unicode ✓',
                'photo' => UploadedFile::fake()->createWithContent('photo test.jpg', 'binary-image-data'),
            ])
            ->assertOk()
            ->assertExactJson($telegramResponse);

        Http::assertSent(fn (Request $request): bool => str_ends_with(
            $request->url(),
            '/v1/telegram/multipart',
        ) && str_contains($request->body(), 'Подпись с Unicode ✓')
            && str_contains($request->body(), 'binary-image-data')
            && $request->hasHeader('X-BotGate-Bot-Token', self::BOT_TOKEN)
            && $request->hasHeader('X-BotGate-Telegram-Method', 'sendPhoto'));
    }

    public function test_multiple_files_and_attach_references_are_preserved(): void
    {
        [$user, $apiKey] = $this->userWithApiKey();
        $bot = $this->botFor($user);
        ProxyNode::factory()->create(['url' => 'https://proxy.example.test']);
        $telegramResponse = ['ok' => true, 'result' => [
            ['message_id' => 44],
            ['message_id' => 45],
        ]];
        $media = json_encode([
            ['type' => 'photo', 'media' => 'attach://first_photo', 'caption' => 'Первое ✓'],
            ['type' => 'photo', 'media' => 'attach://second_photo'],
        ], JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        Http::fake([
            'https://proxy.example.test/v1/telegram/multipart' => Http::response($telegramResponse),
        ]);

        $this->withToken($apiKey)
            ->withHeader('Content-Type', 'multipart/form-data; boundary=testing-multiple')
            ->post("/api/v1/bots/{$bot->public_id}/sendMediaGroup", [
                'chat_id' => '123456789',
                'media' => $media,
                'first_photo' => UploadedFile::fake()->createWithContent(
                    'first photo.jpg',
                    'first-binary-image',
                ),
                'second_photo' => UploadedFile::fake()->createWithContent(
                    'second-photo.jpg',
                    'second-binary-image',
                ),
            ])
            ->assertOk()
            ->assertExactJson($telegramResponse);

        Http::assertSent(fn (Request $request): bool => str_ends_with(
            $request->url(),
            '/v1/telegram/multipart',
        ) && $request->hasHeader('X-BotGate-Telegram-Method', 'sendMediaGroup')
            && str_contains($request->body(), 'attach://first_photo')
            && str_contains($request->body(), 'attach://second_photo')
            && str_contains($request->body(), 'Первое ✓')
            && str_contains($request->body(), 'name="first_photo"')
            && str_contains($request->body(), 'filename="first photo.jpg"')
            && str_contains($request->body(), 'first-binary-image')
            && str_contains($request->body(), 'name="second_photo"')
            && str_contains($request->body(), 'filename="second-photo.jpg"')
            && str_contains($request->body(), 'second-binary-image'));
    }

    public function test_file_download_streams_body_and_upstream_headers(): void
    {
        [$user, $apiKey] = $this->userWithApiKey();
        $bot = $this->botFor($user);
        ProxyNode::factory()->create(['url' => 'https://proxy.example.test']);
        Http::fake([
            'https://proxy.example.test/v1/telegram/file' => Http::response(
                'pdf-binary-content',
                200,
                ['Content-Type' => 'application/pdf', 'Content-Length' => '18'],
            ),
        ]);

        $response = $this->withToken($apiKey)
            ->get("/api/v1/bots/{$bot->public_id}/files/documents/report.pdf");

        $response->assertOk()
            ->assertHeader('Content-Type', 'application/pdf')
            ->assertHeader('Content-Length', '18');
        $this->assertSame('pdf-binary-content', $response->streamedContent());
    }

    public function test_missing_telegram_file_keeps_the_public_404_response(): void
    {
        [$user, $apiKey] = $this->userWithApiKey();
        $bot = $this->botFor($user);
        ProxyNode::factory()->create(['url' => 'https://proxy.example.test']);
        Http::fake([
            'https://proxy.example.test/v1/telegram/file' => Http::response('not found', 404),
        ]);

        $this->withToken($apiKey)
            ->get("/api/v1/bots/{$bot->public_id}/files/documents/missing.pdf")
            ->assertStatus(404)
            ->assertExactJson(['ok' => false, 'error' => 'Файл не найден или недоступен']);
    }

    public function test_file_connection_error_keeps_the_public_502_response(): void
    {
        [$user, $apiKey] = $this->userWithApiKey();
        $bot = $this->botFor($user);
        ProxyNode::factory()->create(['url' => 'https://proxy.example.test']);
        Http::fake([
            'https://proxy.example.test/v1/telegram/file' => Http::failedConnection('offline'),
        ]);

        $this->withToken($apiKey)
            ->get("/api/v1/bots/{$bot->public_id}/files/documents/report.pdf")
            ->assertStatus(502)
            ->assertExactJson(['ok' => false, 'error' => 'Ошибка соединения с Telegram']);
    }

    public function test_webhook_secret_and_payload_contract_are_preserved(): void
    {
        Queue::fake();
        [$user] = $this->userWithApiKey();
        $bot = $this->botFor($user, 'bot_webhook');
        $bot->update([
            'webhook_url' => 'https://client.example.test/webhook',
            'webhook_secret' => 'webhook-secret-value',
            'webhook_enabled' => true,
        ]);
        $payload = [
            'update_id' => 123456,
            'message' => ['text' => 'Привет ✓', 'chat' => ['id' => 42]],
        ];

        $this->postJson("/api/v1/telegram/webhook/{$bot->public_id}/wrong-secret", $payload)
            ->assertStatus(403)
            ->assertExactJson(['ok' => false]);

        $this->postJson("/api/v1/telegram/webhook/{$bot->public_id}/webhook-secret-value", $payload)
            ->assertOk()
            ->assertExactJson(['ok' => true]);

        $event = WebhookEvent::query()->sole();
        $this->assertSame($payload, $event->payload);
        Queue::assertPushed(DeliverWebhookEventJob::class);
    }

    /** @return array{User, string} */
    private function userWithApiKey(): array
    {
        return $this->attachApiKey(User::factory()->create());
    }

    /** @return array{User, string} */
    private function attachApiKey(User $user): array
    {
        $apiKey = 'bg_live_'.bin2hex(random_bytes(16));
        $user->update(['api_key_hash' => app(ApiKeyService::class)->hash($apiKey)]);

        return [$user, $apiKey];
    }

    private function botFor(
        User $user,
        string $publicId = 'bot_compatibility',
        BotStatus $status = BotStatus::Active,
    ): Bot {
        return Bot::query()->create([
            'user_id' => $user->id,
            'public_id' => $publicId,
            'name' => 'Compatibility Bot',
            'telegram_token_encrypted' => self::BOT_TOKEN,
            'telegram_token_hash' => hash('sha256', self::BOT_TOKEN.$publicId),
            'status' => $status,
        ]);
    }
}
