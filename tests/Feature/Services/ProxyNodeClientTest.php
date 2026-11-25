<?php

declare(strict_types=1);

namespace Tests\Feature\Services;

use App\Models\ProxyNode;
use App\Services\Proxy\ProxyNodeClient;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use LogicException;
use Ramsey\Uuid\Uuid;
use Tests\TestCase;

final class ProxyNodeClientTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        Date::setTestNow();
        Str::createUuidsNormally();

        parent::tearDown();
    }

    public function test_it_sends_the_exact_go_compatible_hmac_request(): void
    {
        $secret = '0123456789abcdef0123456789abcdef';
        $requestId = '018f47a2-4c3b-7d6e-8f90-123456789abc';
        $timestamp = 1_800_000_000;
        $node = ProxyNode::factory()->create([
            'node_id' => 'proxy-test',
            'url' => 'https://proxy.example.test',
            'secret_encrypted' => $secret,
        ]);
        Date::setTestNow(Date::createFromTimestamp($timestamp));
        Str::createUuidsUsing(fn () => Uuid::fromString($requestId));
        Http::fake([
            'https://proxy.example.test/v1/telegram/json' => Http::response([
                'ok' => true,
                'result' => ['id' => 123],
            ]),
        ]);

        $response = app(ProxyNodeClient::class)->sendJson(
            $node,
            '123456789:abcdefghijklmnopqrstuvwx',
            'getMe',
            [],
        );

        $this->assertTrue($response->successful());
        Http::assertSent(function (Request $request) use ($requestId, $secret, $timestamp): bool {
            $body = '{"bot_token":"123456789:abcdefghijklmnopqrstuvwx","method":"getMe","params":{}}';
            $canonical = implode("\n", [
                (string) $timestamp,
                $requestId,
                'POST',
                '/v1/telegram/json',
                hash('sha256', $body),
            ]);
            $expectedSignature = hash_hmac('sha256', $canonical, $secret);

            return $request->url() === 'https://proxy.example.test/v1/telegram/json'
                && $request->method() === 'POST'
                && $request->body() === $body
                && $request->hasHeader('X-BotGate-Timestamp', (string) $timestamp)
                && $request->hasHeader('X-BotGate-Request-Id', $requestId)
                && $request->hasHeader('X-BotGate-Signature', $expectedSignature);
        });
    }

    public function test_it_rejects_an_insecure_proxy_url_before_sending(): void
    {
        Http::fake();
        $node = ProxyNode::factory()->create([
            'url' => 'http://proxy.example.test',
        ]);

        try {
            app(ProxyNodeClient::class)->sendJson($node, 'token', 'getMe', []);
            $this->fail('An insecure proxy URL was accepted.');
        } catch (LogicException) {
            // Expected: proxy traffic must use HTTPS.
        }

        Http::assertNothingSent();
    }

    public function test_it_signs_the_exact_multipart_body_and_metadata(): void
    {
        $secret = '0123456789abcdef0123456789abcdef';
        $requestId = '018f47a2-4c3b-7d6e-8f90-123456789abc';
        $timestamp = 1_800_000_000;
        $node = ProxyNode::factory()->create([
            'url' => 'https://proxy.example.test',
            'secret_encrypted' => $secret,
        ]);
        $file = UploadedFile::fake()->createWithContent('photo.jpg', 'binary-image-data');
        Date::setTestNow(Date::createFromTimestamp($timestamp));
        Str::createUuidsUsing(fn () => Uuid::fromString($requestId));
        Http::fake([
            'https://proxy.example.test/v1/telegram/multipart' => Http::response(['ok' => true]),
        ]);

        app(ProxyNodeClient::class)->sendMultipart(
            $node,
            '123456789:abcdefghijklmnopqrstuvwx',
            'sendPhoto',
            ['chat_id' => '123', 'caption' => 'Привет ✓'],
            ['photo' => [
                'path' => $file->getRealPath(),
                'filename' => $file->getClientOriginalName(),
                'mime_type' => $file->getClientMimeType(),
            ]],
        );

        Http::assertSent(function (Request $request) use ($requestId, $secret, $timestamp): bool {
            $canonical = implode("\n", [
                (string) $timestamp,
                $requestId,
                'POST',
                '/v1/telegram/multipart',
                hash('sha256', $request->body()),
                '123456789:abcdefghijklmnopqrstuvwx',
                'sendPhoto',
            ]);

            return $request->hasHeader('X-BotGate-Signature', hash_hmac('sha256', $canonical, $secret))
                && $request->hasHeader('X-BotGate-Bot-Token', '123456789:abcdefghijklmnopqrstuvwx')
                && $request->hasHeader('X-BotGate-Telegram-Method', 'sendPhoto')
                && str_contains($request->body(), 'binary-image-data')
                && str_contains($request->body(), 'Привет ✓');
        });
    }

    public function test_it_signs_and_streams_a_file_download_request(): void
    {
        $secret = '0123456789abcdef0123456789abcdef';
        $requestId = '018f47a2-4c3b-7d6e-8f90-123456789abc';
        $timestamp = 1_800_000_000;
        $node = ProxyNode::factory()->create([
            'url' => 'https://proxy.example.test',
            'secret_encrypted' => $secret,
        ]);
        Date::setTestNow(Date::createFromTimestamp($timestamp));
        Str::createUuidsUsing(fn () => Uuid::fromString($requestId));
        Http::fake([
            'https://proxy.example.test/v1/telegram/file' => Http::response(
                'pdf-binary-content',
                200,
                ['Content-Type' => 'application/pdf', 'Content-Length' => '18'],
            ),
        ]);

        $response = app(ProxyNodeClient::class)->downloadFile(
            $node,
            '123456789:abcdefghijklmnopqrstuvwx',
            'documents/report.pdf',
        );

        $this->assertTrue($response->successful());
        $this->assertSame('application/pdf', $response->header('Content-Type'));
        Http::assertSent(function (Request $request) use ($requestId, $secret, $timestamp): bool {
            $body = '{"bot_token":"123456789:abcdefghijklmnopqrstuvwx","file_path":"documents/report.pdf"}';
            $canonical = implode("\n", [
                (string) $timestamp,
                $requestId,
                'POST',
                '/v1/telegram/file',
                hash('sha256', $body),
            ]);

            return $request->url() === 'https://proxy.example.test/v1/telegram/file'
                && $request->method() === 'POST'
                && $request->body() === $body
                && $request->hasHeader('X-BotGate-Signature', hash_hmac('sha256', $canonical, $secret));
        });
    }
}
