<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Enums\ProxyNodeStatus;
use App\Models\ProxyNode;
use Illuminate\Console\Command;

final class AddProxyNodeCommand extends Command
{
    protected $signature = 'botgate:proxy-node:add
        {node_id : NODE_ID configured on the Go proxy}
        {url : HTTPS origin, for example https://proxy-02.bot-gate.ru}
        {--name= : Human-readable node name}
        {--priority=100 : Lower values are selected first}
        {--enable : Enable routing immediately}';

    protected $description = 'Добавляет Proxy Node с безопасным вводом уникального NODE_SECRET';

    public function handle(): int
    {
        $nodeId = (string) $this->argument('node_id');
        $url = rtrim((string) $this->argument('url'), '/');
        $name = (string) ($this->option('name') ?: $nodeId);
        $priority = filter_var(
            $this->option('priority'),
            FILTER_VALIDATE_INT,
            ['options' => ['min_range' => 0, 'max_range' => 65_535]],
        );

        if (! preg_match('/^[A-Za-z0-9][A-Za-z0-9._-]{0,63}$/', $nodeId)) {
            $this->error('NODE_ID имеет недопустимый формат.');

            return self::FAILURE;
        }

        if (! $this->isValidHttpsOrigin($url)) {
            $this->error('URL должен быть чистым HTTPS origin без credentials, path, query или fragment.');

            return self::FAILURE;
        }

        if ($priority === false) {
            $this->error('Priority должен быть целым числом от 0 до 65535.');

            return self::FAILURE;
        }

        if (ProxyNode::query()->where('node_id', $nodeId)->orWhere('url', $url)->exists()) {
            $this->error('Proxy Node с таким NODE_ID или URL уже существует.');

            return self::FAILURE;
        }

        $secret = $this->secret('NODE_SECRET (минимум 32 символа)');

        if (! is_string($secret) || strlen($secret) < 32) {
            $this->error('NODE_SECRET должен содержать минимум 32 символа.');

            return self::FAILURE;
        }

        $node = ProxyNode::query()->create([
            'node_id' => $nodeId,
            'name' => $name,
            'url' => $url,
            'secret_encrypted' => $secret,
            'priority' => $priority,
            'enabled' => (bool) $this->option('enable'),
            'status' => ProxyNodeStatus::Unknown,
        ]);

        $this->info("Proxy Node {$node->node_id} добавлен.");
        $this->table(
            ['URL', 'Priority', 'Enabled', 'Status'],
            [[
                $node->url,
                $node->priority,
                $node->enabled ? 'Да' : 'Нет',
                $node->status->label(),
            ]],
        );

        return self::SUCCESS;
    }

    private function isValidHttpsOrigin(string $url): bool
    {
        $parts = parse_url($url);

        return $parts !== false
            && ($parts['scheme'] ?? null) === 'https'
            && ! empty($parts['host'])
            && empty($parts['user'])
            && empty($parts['pass'])
            && empty($parts['query'])
            && empty($parts['fragment'])
            && in_array($parts['path'] ?? '', ['', '/'], true);
    }
}
