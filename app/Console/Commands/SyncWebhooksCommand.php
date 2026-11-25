<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Enums\BotStatus;
use App\Models\Bot;
use App\Services\TelegramService;
use App\Services\TelegramWebhookUrl;
use Illuminate\Console\Command;

final class SyncWebhooksCommand extends Command
{
    protected $signature = 'botgate:webhooks:sync
        {--apply : Register changes; otherwise read-only report}
        {--bot= : Only this bot public ID}
        {--base-url= : Target HTTPS origin}
        {--from-base-url= : Only this explicitly approved source HTTPS origin}';

    protected $description = 'Safely move managed Telegram webhooks, with read-back verification';

    public function handle(TelegramService $telegram, TelegramWebhookUrl $urls): int
    {
        try {
            $target = $this->option('base-url');
            $from = $this->option('from-base-url');
            if ($target !== null) {
                $target = $urls->validateBase($target);
            }
            if ($from !== null) {
                $from = $urls->validateBase($from);
            }
            // Validate configured origins even for an empty selection, without disclosing them.
            foreach ([$urls->configuredBase(), ...$urls->allowedBases()] as $base) {
                if ($base !== null && $base !== '') {
                    $urls->validateBase($base);
                }
            }
        } catch (\Throwable) {
            $this->error('Invalid webhook base configuration: use HTTPS origins without credentials, path, query or fragment.');

            return self::INVALID;
        }

        $counts = ['changed' => 0, 'skipped' => 0, 'conflict' => 0, 'failed' => 0, 'unknown' => 0];
        $planned = 0;
        $query = Bot::query()->where('status', BotStatus::Active)->where('webhook_enabled', true)
            ->whereNotNull('webhook_url')->where('webhook_url', '!=', '');
        if ($this->option('bot') !== null) {
            $query->where('public_id', $this->option('bot'));
        }
        $this->info($this->option('apply') ? 'APPLY: only managed webhooks will be moved.' : 'DRY RUN: no setWebhook and no database changes.');
        $seen = 0;
        foreach ($query->lazyById() as $bot) {
            $seen++;
            try {
                [$state, $detail] = $this->sync($bot, $telegram, $urls, $target, $from);
            } catch (\Throwable) {
                // Never print exception messages, Telegram descriptions, tokens or full URLs.
                [$state, $detail] = ['failed', 'local error; no automatic retry'];
            }
            $counts[$state]++;
            if ($detail === 'would change') {
                $planned++;
            }
            $safeId = preg_replace('/[^A-Za-z0-9_-]/', '_', substr((string) $bot->public_id, 0, 64));
            $this->line("{$safeId}: {$state} ({$detail})");
        }
        if ($seen === 0 && $this->option('bot') !== null) {
            $counts['failed']++;
            $this->error('Selected bot is missing, deleted or not eligible.');
        }
        $this->line(implode(' ', array_map(static fn (string $key, int $count): string => "{$key}={$count}", array_keys($counts), $counts))." planned={$planned}");

        // Unknown outcome has priority so scripts never interpret it as a definite rejection.
        return $counts['unknown'] > 0 ? 3 : (($counts['failed'] + $counts['conflict']) > 0 ? self::FAILURE : self::SUCCESS);
    }

    /** @return array{string, string} */
    private function sync(Bot $bot, TelegramService $telegram, TelegramWebhookUrl $urls, ?string $target, ?string $from): array
    {
        $desired = $urls->forBot($bot, $target);
        $token = $bot->telegram_token_encrypted;
        $before = $this->readInfo($telegram, $token);
        if (is_string($before['failure'] ?? null)) {
            return ['failed', $before['failure']];
        }
        $current = $before['url'] ?? null;
        if (! is_string($current)) {
            return ['failed', 'invalid webhook info'];
        }
        if ($current === '') {
            return ['conflict', 'webhook absent; not captured'];
        }
        if ($current === $desired) {
            return ['skipped', 'already at target'.$this->safeInfo($before)];
        }
        if (! $urls->isManaged($bot, $current, $from === null ? [] : [$from])) {
            return ['conflict', 'foreign webhook; not captured'];
        }
        if ($from !== null && $current !== $urls->forBot($bot, $from)) {
            return ['skipped', 'source filter mismatch'];
        }
        if (($before['has_custom_certificate'] ?? false) === true) {
            return ['conflict', 'custom certificate requires separate handling'];
        }
        if (! $this->option('apply')) {
            return ['skipped', 'would change'];
        }

        $params = ['url' => $desired, 'drop_pending_updates' => false];
        foreach (['allowed_updates', 'max_connections'] as $key) {
            if (array_key_exists($key, $before)) {
                $params[$key] = $before[$key];
            }
        }
        // Do not copy ip_address: WebhookInfo cannot distinguish DNS from an old pinned IP.
        // secret_token is not readable; BotGate authenticates the unchanged secret in the path.
        try {
            $result = $telegram->proxyJson($token, 'setWebhook', $params);
        } catch (\Throwable) {
            $result = []; // May already be applied. Read back once, never repeat the POST.
        }
        if (($result['ok'] ?? null) === false && is_int($result['error_code'] ?? null)
            && $result['error_code'] >= 400 && $result['error_code'] < 500) {
            return ['failed', $this->errorDetail($result)];
        }
        $after = $this->readInfo($telegram, $token);
        if (! isset($after['failure']) && $after['url'] === $desired) {
            return ['changed', 'confirmed'.$this->safeInfo($after)];
        }

        return ['unknown', is_string($after['failure'] ?? null) ? 'confirmation unavailable; '.$after['failure'] : 'target not confirmed; inspect before rerun'];
    }

    /** @return array<string, mixed> */
    private function readInfo(TelegramService $telegram, string $token): array
    {
        try {
            $response = $telegram->proxyJson($token, 'getWebhookInfo', []);
        } catch (\Throwable) {
            return ['failure' => 'getWebhookInfo unavailable; no automatic retry'];
        }
        if (($response['ok'] ?? null) !== true || ! is_array($response['result'] ?? null)
            || ! is_string($response['result']['url'] ?? null)) {
            return ['failure' => $this->errorDetail($response)];
        }

        return $response['result'];
    }

    /** @param array<string, mixed> $response */
    private function errorDetail(array $response): string
    {
        $code = is_int($response['error_code'] ?? null) ? $response['error_code'] : 0;
        $parameters = $response['parameters'] ?? null;
        $retry = is_array($parameters) ? ($parameters['retry_after'] ?? null) : null;

        return 'Telegram error '.$code.(is_int($retry) && $retry > 0 ? "; retry_after={$retry}s; rerun no earlier" : '; no automatic retry');
    }

    /** @param array<string, mixed> $info */
    private function safeInfo(array $info): string
    {
        $ip = $info['ip_address'] ?? null;
        $pending = $info['pending_update_count'] ?? null;

        return (is_string($ip) && filter_var($ip, FILTER_VALIDATE_IP) ? "; ip={$ip}" : '')
            .(is_int($pending) ? "; pending={$pending}" : '');
    }
}
