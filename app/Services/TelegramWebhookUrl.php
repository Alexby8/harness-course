<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Bot;
use Illuminate\Support\Facades\Config;
use InvalidArgumentException;

final class TelegramWebhookUrl
{
    public function forBot(Bot $bot, ?string $baseUrl = null): string
    {
        $baseUrl ??= $this->configuredBase();
        if ($baseUrl === null || $baseUrl === '') {
            return $this->legacy($bot);
        }

        return $this->validateBase($baseUrl).$this->path($bot);
    }

    /** @param list<string> $additionalBases Explicit operator-approved migration addresses. */
    public function isManaged(Bot $bot, string $url, array $additionalBases = []): bool
    {
        $urls = [$this->legacy($bot), $this->forBot($bot)];
        foreach ([...$this->allowedBases(), ...$additionalBases] as $base) {
            $urls[] = $this->forBot($bot, $base);
        }

        return in_array($url, $urls, true);
    }

    public function validateBase(string $base): string
    {
        $parts = parse_url($base);
        if (! is_array($parts) || ($parts['scheme'] ?? '') !== 'https'
            || empty($parts['host']) || isset($parts['user']) || isset($parts['pass'])
            || isset($parts['query']) || isset($parts['fragment'])
            || ! in_array($parts['path'] ?? '', ['', '/'], true)
            || filter_var($base, FILTER_VALIDATE_URL) === false) {
            throw new InvalidArgumentException('Webhook base URL must be an HTTPS origin without credentials, path, query or fragment.');
        }

        return rtrim($base, '/');
    }

    public function configuredBase(): ?string
    {
        $base = config('botgate.webhook.base_url');
        if ($base !== null && ! is_string($base)) {
            throw new InvalidArgumentException('Invalid webhook base configuration.');
        }

        return $base;
    }

    /** @return list<string> */
    public function allowedBases(): array
    {
        $bases = config('botgate.webhook.allowed_base_urls', []);
        if (! is_array($bases)) {
            throw new InvalidArgumentException('Invalid webhook allowed origins.');
        }
        $result = [];
        foreach ($bases as $base) {
            if (! is_string($base)) {
                throw new InvalidArgumentException('Invalid webhook allowed origin.');
            }
            $result[] = $this->validateBase($base);
        }

        return $result;
    }

    private function legacy(Bot $bot): string
    {
        // APP_URL is an explicit trusted origin; never trust the incoming Host header.
        return rtrim(Config::string('app.url'), '/').$this->path($bot);
    }

    private function path(Bot $bot): string
    {
        return route('telegram.webhook', ['botPublicId' => $bot->public_id, 'secret' => $bot->webhook_secret], false);
    }
}
