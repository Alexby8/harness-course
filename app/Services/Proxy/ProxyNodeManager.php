<?php

declare(strict_types=1);

namespace App\Services\Proxy;

use App\Exceptions\NoAvailableProxyNodeException;
use App\Models\ProxyNode;
use Illuminate\Http\Client\Response;

final readonly class ProxyNodeManager
{
    public function __construct(
        private ProxyNodeClient $client,
    ) {}

    public function primaryNode(): ProxyNode
    {
        $node = ProxyNode::query()->routable()->first();

        if ($node === null) {
            throw new NoAvailableProxyNodeException;
        }

        return $node;
    }

    /**
     * @param  array<string, mixed>  $params
     */
    public function sendJson(string $botToken, string $method, array $params): Response
    {
        // Do not retry another node here. Once an HTTP request starts, a timeout
        // cannot prove that Telegram did not process the original request.
        return $this->client->sendJson(
            node: $this->primaryNode(),
            botToken: $botToken,
            method: $method,
            params: $params,
        );
    }

    /**
     * @param  array<string, mixed>  $fields
     * @param  array<string, array{path: string, filename: string, mime_type: ?string}>  $files
     */
    public function sendMultipart(string $botToken, string $method, array $fields, array $files): Response
    {
        return $this->client->sendMultipart(
            node: $this->primaryNode(),
            botToken: $botToken,
            method: $method,
            fields: $fields,
            files: $files,
        );
    }

    public function downloadFile(string $botToken, string $filePath): Response
    {
        return $this->client->downloadFile(
            node: $this->primaryNode(),
            botToken: $botToken,
            filePath: $filePath,
        );
    }
}
