<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\ApiRequestLog;
use App\Models\WebhookEvent;
use App\Enums\WebhookEventStatus;
use Illuminate\Console\Command;

class CleanupLogsCommand extends Command
{
    protected $signature = 'botgate:cleanup-logs';
    protected $description = 'Удаляет устаревшие логи и webhook события';

    public function handle(): int
    {
        $apiDeleted = ApiRequestLog::where('created_at', '<', now()->subDays(14))->delete();
        $this->info("API логи: удалено {$apiDeleted} записей (старше 14 дней).");

        $webhookDeleted = WebhookEvent::where('status', '!=', WebhookEventStatus::Failed)
            ->where('created_at', '<', now()->subDays(30))
            ->delete();
        $this->info("Webhook события: удалено {$webhookDeleted} записей (старше 30 дней).");

        $failedDeleted = WebhookEvent::where('status', WebhookEventStatus::Failed)
            ->where('created_at', '<', now()->subDays(60))
            ->delete();
        $this->info("Failed webhook события: удалено {$failedDeleted} записей (старше 60 дней).");

        return Command::SUCCESS;
    }
}
