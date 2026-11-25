<?php

declare(strict_types=1);

namespace App\Services;

use App\Exceptions\NoAvailableProxyNodeException;
use App\Services\Proxy\ProxyNodeManager;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Response;

final class TelegramService
{
    public function __construct(
        private readonly ProxyNodeManager $proxyNodes,
    ) {}

    /**
     * Validate token and return bot info from Telegram getMe.
     *
     * @return array<string, mixed>|null null if token is invalid
     */
    public function getMe(string $token): ?array
    {
        try {
            $response = $this->proxyNodes->sendJson($token, 'getMe', []);
        } catch (ConnectionException|NoAvailableProxyNodeException) {
            return null;
        }

        if (! $response->successful()) {
            return null;
        }

        $data = $response->json();

        if (! ($data['ok'] ?? false)) {
            return null;
        }

        return $data['result'];
    }

    /**
     * Get current webhook info from Telegram for a given bot token.
     *
     * @return array<string, mixed>|null null on failure
     */
    public function getWebhookInfo(string $token): ?array
    {
        try {
            $response = $this->proxyNodes->sendJson($token, 'getWebhookInfo', []);
        } catch (ConnectionException|NoAvailableProxyNodeException) {
            return null;
        }

        if (! $response->successful()) {
            return null;
        }

        $data = $response->json();

        if (! ($data['ok'] ?? false)) {
            return null;
        }

        return $data['result'];
    }

    /**
     * Proxy a JSON request to Telegram API.
     *
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    public function proxyJson(string $token, string $method, array $data): array
    {
        $response = $this->proxyNodes->sendJson($token, $method, $data);

        return $this->formatResponse($response);
    }

    /**
     * Proxy a multipart/form-data request to Telegram API.
     *
     * @param  array<string, mixed>  $fields
     * @param  array<string, array{path: string, filename: string, mime_type: ?string}>  $files
     * @return array<string, mixed>
     */
    public function proxyMultipart(string $token, string $method, array $fields, array $files): array
    {
        $response = $this->proxyNodes->sendMultipart($token, $method, $fields, $files);

        return $this->formatResponse($response);
    }

    /**
     * Stream a file from Telegram file storage.
     * Returns null if the file does not exist or the token is invalid.
     */
    public function getFileStream(string $token, string $filePath): ?Response
    {
        $response = $this->proxyNodes->downloadFile($token, $filePath);

        if (! $response->successful()) {
            return null;
        }

        return $response;
    }

    /**
     * @return array<string, mixed>
     */
    private function formatResponse(Response $response): array
    {
        $body = $response->json();

        if (! is_array($body)) {
            return [
                'ok' => false,
                'error' => 'Некорректный ответ от Telegram',
            ];
        }

        return $body;
    }
}
