<?php

declare(strict_types=1);

namespace App\Services\Proxy;

use App\Models\ProxyNode;
use GuzzleHttp\Psr7\MultipartStream;
use GuzzleHttp\Psr7\Utils;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use JsonException;
use LogicException;

final class ProxyNodeClient
{
    private const HEALTH_ENDPOINT = '/health';

    private const JSON_ENDPOINT = '/v1/telegram/json';

    private const MULTIPART_ENDPOINT = '/v1/telegram/multipart';

    private const FILE_ENDPOINT = '/v1/telegram/file';

    private const HEADER_TIMESTAMP = 'X-BotGate-Timestamp';

    private const HEADER_REQUEST_ID = 'X-BotGate-Request-Id';

    private const HEADER_SIGNATURE = 'X-BotGate-Signature';

    private const HEADER_BOT_TOKEN = 'X-BotGate-Bot-Token';

    private const HEADER_TELEGRAM_METHOD = 'X-BotGate-Telegram-Method';

    /**
     * @param  array<string, mixed>  $params
     *
     * @throws ConnectionException
     * @throws JsonException
     */
    public function sendJson(ProxyNode $node, string $botToken, string $method, array $params): Response
    {
        $baseUrl = $this->validatedBaseUrl($node);
        $secret = $this->validatedSecret($node);
        $body = json_encode([
            'bot_token' => $botToken,
            'method' => $method,
            'params' => (object) $params,
        ], JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES);
        $timestamp = (string) Date::now()->getTimestamp();
        $requestId = (string) Str::uuid();
        $signature = $this->signature(
            secret: $secret,
            timestamp: $timestamp,
            requestId: $requestId,
            method: 'POST',
            requestUri: self::JSON_ENDPOINT,
            body: $body,
        );
        $startedAt = hrtime(true);

        try {
            $response = Http::acceptJson()
                ->connectTimeout((float) config('botgate.proxy.connect_timeout', 3))
                ->timeout((float) config('botgate.proxy.json_timeout', 35))
                ->withHeaders([
                    self::HEADER_TIMESTAMP => $timestamp,
                    self::HEADER_REQUEST_ID => $requestId,
                    self::HEADER_SIGNATURE => $signature,
                ])
                ->withBody($body, 'application/json')
                ->post($baseUrl.self::JSON_ENDPOINT);
        } catch (ConnectionException $exception) {
            Log::warning('Proxy node JSON request failed without a response', [
                'proxy_node_id' => $node->getKey(),
                'node_id' => $node->node_id,
                'request_id' => $requestId,
                'telegram_method' => $method,
                'duration_ms' => $this->durationMilliseconds($startedAt),
            ]);

            throw $exception;
        }

        Log::info('Proxy node JSON request completed', [
            'proxy_node_id' => $node->getKey(),
            'node_id' => $node->node_id,
            'request_id' => $requestId,
            'telegram_method' => $method,
            'http_status' => $response->status(),
            'duration_ms' => $this->durationMilliseconds($startedAt),
        ]);

        return $response;
    }

    /** @throws ConnectionException */
    public function health(ProxyNode $node): Response
    {
        return Http::acceptJson()
            ->connectTimeout((float) config('botgate.proxy.connect_timeout', 3))
            ->timeout((float) config('botgate.proxy.health_timeout', 5))
            ->get($this->validatedBaseUrl($node).self::HEALTH_ENDPOINT);
    }

    /**
     * @param  array<string, mixed>  $fields
     * @param  array<string, array{path: string, filename: string, mime_type: ?string}>  $files
     *
     * @throws ConnectionException
     */
    public function sendMultipart(
        ProxyNode $node,
        string $botToken,
        string $method,
        array $fields,
        array $files,
    ): Response {
        $parts = [];

        foreach ($fields as $name => $value) {
            $parts[] = ['name' => $name, 'contents' => (string) $value];
        }

        foreach ($files as $name => $file) {
            $parts[] = array_filter([
                'name' => $name,
                'contents' => Utils::tryFopen($file['path'], 'rb'),
                'filename' => $file['filename'],
                'headers' => $file['mime_type'] ? ['Content-Type' => $file['mime_type']] : null,
            ], fn (mixed $value): bool => $value !== null);
        }

        $boundary = 'botgate-'.Str::lower((string) Str::uuid());
        $body = new MultipartStream($parts, $boundary);
        $bodyHash = hash_init('sha256');

        while (! $body->eof()) {
            hash_update($bodyHash, $body->read(8192));
        }

        $body->rewind();
        $timestamp = (string) Date::now()->getTimestamp();
        $requestId = (string) Str::uuid();
        $signature = $this->signatureFromHash(
            secret: $this->validatedSecret($node),
            timestamp: $timestamp,
            requestId: $requestId,
            method: 'POST',
            requestUri: self::MULTIPART_ENDPOINT,
            bodyHash: hash_final($bodyHash),
            extra: [$botToken, $method],
        );

        return Http::acceptJson()
            ->connectTimeout((float) config('botgate.proxy.connect_timeout', 3))
            ->timeout((float) config('botgate.proxy.multipart_timeout', 95))
            ->withHeaders([
                self::HEADER_TIMESTAMP => $timestamp,
                self::HEADER_REQUEST_ID => $requestId,
                self::HEADER_SIGNATURE => $signature,
                self::HEADER_BOT_TOKEN => $botToken,
                self::HEADER_TELEGRAM_METHOD => $method,
            ])
            ->withBody($body, 'multipart/form-data; boundary='.$boundary)
            ->post($this->validatedBaseUrl($node).self::MULTIPART_ENDPOINT);
    }

    /**
     * @throws ConnectionException
     * @throws JsonException
     */
    public function downloadFile(ProxyNode $node, string $botToken, string $filePath): Response
    {
        $body = json_encode([
            'bot_token' => $botToken,
            'file_path' => $filePath,
        ], JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES);
        $timestamp = (string) Date::now()->getTimestamp();
        $requestId = (string) Str::uuid();
        $signature = $this->signature(
            secret: $this->validatedSecret($node),
            timestamp: $timestamp,
            requestId: $requestId,
            method: 'POST',
            requestUri: self::FILE_ENDPOINT,
            body: $body,
        );
        $startedAt = hrtime(true);

        try {
            $response = Http::connectTimeout((float) config('botgate.proxy.connect_timeout', 3))
                ->timeout((float) config('botgate.proxy.file_timeout', 95))
                ->withOptions(['stream' => true])
                ->withHeaders([
                    self::HEADER_TIMESTAMP => $timestamp,
                    self::HEADER_REQUEST_ID => $requestId,
                    self::HEADER_SIGNATURE => $signature,
                ])
                ->withBody($body, 'application/json')
                ->post($this->validatedBaseUrl($node).self::FILE_ENDPOINT);
        } catch (ConnectionException $exception) {
            Log::warning('Proxy node file request failed without a response', [
                'proxy_node_id' => $node->getKey(),
                'node_id' => $node->node_id,
                'request_id' => $requestId,
                'duration_ms' => $this->durationMilliseconds($startedAt),
            ]);

            throw $exception;
        }

        Log::info('Proxy node file request completed', [
            'proxy_node_id' => $node->getKey(),
            'node_id' => $node->node_id,
            'request_id' => $requestId,
            'http_status' => $response->status(),
            'duration_ms' => $this->durationMilliseconds($startedAt),
        ]);

        return $response;
    }

    private function validatedBaseUrl(ProxyNode $node): string
    {
        $url = rtrim((string) $node->url, '/');
        $parts = parse_url($url);

        if (
            $parts === false
            || ($parts['scheme'] ?? null) !== 'https'
            || empty($parts['host'])
            || ! empty($parts['user'])
            || ! empty($parts['pass'])
            || ! empty($parts['query'])
            || ! empty($parts['fragment'])
            || ! in_array($parts['path'] ?? '', ['', '/'], true)
        ) {
            throw new LogicException("Proxy node {$node->node_id} must use an HTTPS origin URL without credentials, query, fragment, or path.");
        }

        return $url;
    }

    private function validatedSecret(ProxyNode $node): string
    {
        $secret = $node->secret_encrypted;

        if (strlen($secret) < 32) {
            throw new LogicException("Proxy node {$node->node_id} must have a secret containing at least 32 characters.");
        }

        return $secret;
    }

    private function signature(
        string $secret,
        string $timestamp,
        string $requestId,
        string $method,
        string $requestUri,
        string $body,
    ): string {
        return $this->signatureFromHash(
            secret: $secret,
            timestamp: $timestamp,
            requestId: $requestId,
            method: $method,
            requestUri: $requestUri,
            bodyHash: hash('sha256', $body),
        );
    }

    /** @param list<string> $extra */
    private function signatureFromHash(
        string $secret,
        string $timestamp,
        string $requestId,
        string $method,
        string $requestUri,
        string $bodyHash,
        array $extra = [],
    ): string {
        $canonical = implode("\n", [
            $timestamp,
            $requestId,
            $method,
            $requestUri,
            $bodyHash,
            ...$extra,
        ]);

        return hash_hmac('sha256', $canonical, $secret);
    }

    private function durationMilliseconds(int $startedAt): int
    {
        return (int) round((hrtime(true) - $startedAt) / 1_000_000);
    }
}
