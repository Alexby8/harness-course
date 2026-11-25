<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\ProxyNodeHealthCheck;
use Illuminate\Console\Command;

final class PruneProxyNodeHealthChecksCommand extends Command
{
    protected $signature = 'botgate:proxy-node:prune-health-checks';

    protected $description = 'Удаляет сырые health-check старше настроенного срока хранения';

    public function handle(): int
    {
        $retentionDays = max(1, (int) config('botgate.proxy.health_history_retention_days', 90));
        $deleted = ProxyNodeHealthCheck::query()
            ->where('checked_at', '<', now()->subDays($retentionDays))
            ->delete();

        $this->info("Удалено записей: {$deleted}. Срок хранения: {$retentionDays} дн.");

        return self::SUCCESS;
    }
}
