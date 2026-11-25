<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\BotStatus;
use App\Enums\WebhookEventStatus;
use App\Jobs\DeliverWebhookEventJob;
use App\Models\Bot;
use App\Models\BotDailyStat;
use App\Models\WebhookEvent;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Queue;

final class WebhookIngress
{
    /** @param array<string, mixed> $payload */
    public function accept(string $publicId, string $secret, array $payload): bool
    {
        return DB::transaction(function () use ($publicId, $secret, $payload): bool {
            // Serialize receivers for this bot, including old/new ingress URLs.
            $bot = Bot::where('public_id', $publicId)->lockForUpdate()->first();
            if ($bot === null || ! hash_equals((string) $bot->webhook_secret, $secret) || $bot->status !== BotStatus::Active) {
                return false;
            }
            if (empty($bot->webhook_url) || ! $bot->webhook_enabled) {
                return true;
            }
            $updateId = $payload['update_id'] ?? null;
            if ($updateId !== null) {
                $existing = WebhookEvent::where('bot_id', $bot->id)->where('telegram_update_id', $updateId)->orderBy('id')->lockForUpdate()->first();
                if ($existing !== null) {
                    // Recover legacy save-before-dispatch gaps. Job claims prevent double delivery
                    // if a legacy job was already in the queue but had no durable marker.
                    if ($existing->status === WebhookEventStatus::Pending && $existing->attempts === 0 && $existing->queued_at === null) {
                        $this->enqueue($existing);
                    }

                    return true;
                }
            }
            $event = WebhookEvent::create([
                'bot_id' => $bot->id, 'telegram_update_id' => $updateId,
                'payload' => $payload, 'target_url' => $bot->webhook_url, 'status' => WebhookEventStatus::Pending,
            ]);
            // SAME default DB transaction as the event. afterCommit/remote brokers reopen the loss window.
            $this->enqueue($event);
            BotDailyStat::incrementStat($bot->id, 'webhook_received_count');

            return true;
        });
    }

    private function enqueue(WebhookEvent $event): void
    {
        Queue::connection('webhook_ingress')->push((new DeliverWebhookEventJob($event->id))->beforeCommit(), '', 'webhooks');
        $event->update(['queued_at' => now()]);
    }
}
