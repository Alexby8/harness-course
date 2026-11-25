<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\ProxyNode;
use App\Services\Proxy\ProxyNodeHealthChecker;
use Illuminate\Console\Command;

final class CheckProxyNodesCommand extends Command
{
    protected $signature = 'botgate:proxy-node:check
        {node_id? : NODE_ID конкретного узла; без аргумента проверяются все включенные узлы}';

    protected $description = 'Проверяет доступность Proxy Nodes через их health endpoint';

    public function handle(ProxyNodeHealthChecker $checker): int
    {
        $nodeId = $this->argument('node_id');
        $query = ProxyNode::query()->orderBy('priority')->orderBy('id');

        if (is_string($nodeId) && $nodeId !== '') {
            $query->where('node_id', $nodeId);
        } else {
            $query->where('enabled', true);
        }

        $nodes = $query->get();

        if ($nodes->isEmpty()) {
            $this->error($nodeId ? 'Proxy Node не найден.' : 'Нет включенных Proxy Nodes.');

            return self::FAILURE;
        }

        $failed = false;
        $rows = [];

        foreach ($nodes as $node) {
            $result = $checker->check($node);
            $failed = $failed || ! $result->successful;
            $rows[] = [
                $node->node_id,
                $result->successful ? 'Онлайн' : 'Недоступен',
                $result->responseTimeMs.' мс',
                ProxyNode::query()->whereKey($node->getKey())->value('version') ?? '—',
                $result->error ?? '—',
            ];
        }

        $this->table(['NODE_ID', 'Статус', 'Время', 'Версия', 'Ошибка'], $rows);

        return $failed ? self::FAILURE : self::SUCCESS;
    }
}
