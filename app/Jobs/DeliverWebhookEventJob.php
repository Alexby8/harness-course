<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Enums\WebhookEventStatus;
use App\Models\BotDailyStat;
use App\Models\WebhookEvent;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;

class DeliverWebhookEventJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 1;

    public int $timeout = 30;

    // Default also applies to jobs serialized before this field was introduced.
    private ?int $expectedAttempts = null;

    public function __construct(
        private readonly int $eventId,
        int $expectedAttempts = 0,
    ) {
        $this->expectedAttempts = $expectedAttempts;
        $this->onQueue('webhooks');
    }

    public function handle(): void
    {
        $event = WebhookEvent::find($this->eventId);

        if ($event === null || $event->status === WebhookEventStatus::Sent) {
            return;
        }

        // Older serialized jobs have no expectedAttempts. A recovered initial job
        // must not steal a live attempt, while an already queued legacy retry must work.
        if ($this->expectedAttempts === null && $event->status === WebhookEventStatus::Processing) {
            $legacyDelay = $event->attempts === 1 ? 60 : 300;
            if ($event->attempts >= 3 || $event->last_attempt_at === null
                || $event->last_attempt_at->copy()->addSeconds($legacyDelay)->isFuture()) {
                return;
            }
        }

        $claimed = WebhookEvent::whereKey($this->eventId)
            ->where('attempts', $this->expectedAttempts ?? $event->attempts)
            ->whereNotIn('status', [WebhookEventStatus::Sent, WebhookEventStatus::Failed])
            ->update([
                'status' => WebhookEventStatus::Processing,
                'last_attempt_at' => now(),
                'attempts' => DB::raw('attempts + 1'),
            ]);
        if ($claimed === 0) {
            return;
        }
        $event->refresh();

        $bot = $event->bot;
        $payload = json_encode($event->payload);
        $signature = hash_hmac('sha256', $payload, $bot->webhook_secret);

        try {
            $response = Http::timeout(10)
                ->withHeaders([
                    'Content-Type' => 'application/json',
                    'User-Agent' => 'BotGate Webhook',
                    'X-BotGate-Bot-Id' => $bot->public_id,
                    'X-BotGate-Event-Id' => (string) $event->id,
                    'X-BotGate-Signature' => $signature,
                ])
                ->withBody($payload, 'application/json')
                ->post($event->target_url);

            $httpStatus = $response->status();
            $success = $response->successful();
        } catch (\Throwable $e) {
            $httpStatus = null;
            $success = false;
            $error = $e->getMessage();
        }

        if ($success) {
            $event->update([
                'status' => WebhookEventStatus::Sent,
                'last_http_status' => $httpStatus,
                'last_error' => null,
                'delivered_at' => now(),
            ]);
            BotDailyStat::incrementStat($bot->id, 'webhook_sent_count');
            $bot->update(['last_webhook_delivery_at' => now(), 'last_webhook_error' => null]);

            return;
        }

        $error = $error ?? "HTTP {$httpStatus}";
        $event->update([
            'last_http_status' => $httpStatus,
            'last_error' => $error,
        ]);
        $bot->update(['last_webhook_error' => $error]);

        if ($event->attempts < 3) {
            $delays = [0, 60, 300];
            $delay = $delays[$event->attempts];

            static::dispatch($this->eventId, $event->attempts)->onConnection('webhook_ingress')->delay(now()->addSeconds($delay));
        } else {
            $event->update(['status' => WebhookEventStatus::Failed]);
            BotDailyStat::incrementStat($bot->id, 'webhook_failed_count');
        }
    }
}
