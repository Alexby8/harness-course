<?php

declare(strict_types=1);

namespace App\Services;

use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Cookie\CookieValuePrefix;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Str;

final class AnalyticsConsent
{
    /** @return array{id: string, version: string, choice: string, expiresAt: int}|null */
    public function read(Request $request): ?array
    {
        $current = $this->decodeConsent($request->cookie(Config::string('cookie-consent.cookie')));
        if ($current !== null) {
            return $current;
        }

        $legacyName = Config::string('cookie-consent.legacy_cookie');
        $legacy = $this->decodeConsent($request->cookie($legacyName));
        // PHP keeps only one value for duplicate cookie names. Read all legacy
        // candidates, validating encryption and Laravel's name-bound prefix.
        // Do not delete a parent-domain cookie: it may belong to the main site.
        foreach (explode(';', $request->headers->get('cookie') ?? '') as $part) {
            $pair = explode('=', trim($part), 2);
            if (count($pair) !== 2 || $pair[0] !== $legacyName) {
                continue;
            }
            try {
                $stored = CookieValuePrefix::validate(
                    $legacyName,
                    Crypt::decryptString(rawurldecode($pair[1])),
                    Crypt::getAllKeys(),
                );
            } catch (DecryptException) {
                continue;
            }
            $candidate = $this->decodeConsent($stored);
            if ($candidate !== null && ($legacy === null || $candidate['choice'] === 'declined')) {
                $legacy = $candidate;
            }
        }

        return $legacy;
    }

    /** @return array{id: string, version: string, choice: string, expiresAt: int}|null */
    private function decodeConsent(mixed $stored): ?array
    {
        $consent = is_string($stored) ? json_decode($stored, true) : null;

        if (! is_array($consent)
            || ! is_string($consent['version'] ?? null)
            // A document revision must never silently override an existing refusal.
            || (($consent['choice'] ?? null) !== 'declined'
                && $consent['version'] !== Config::string('cookie-consent.version'))
            || ! is_string($consent['id'] ?? null)
            || ! Str::isUuid($consent['id'])
            || ! in_array($consent['choice'] ?? null, ['accepted', 'declined'], true)
            || ! is_int($consent['expiresAt'] ?? null)
            || $consent['expiresAt'] <= now()->getTimestampMs()) {
            return null;
        }

        return [
            'id' => $consent['id'],
            'version' => $consent['version'],
            'choice' => $consent['choice'],
            'expiresAt' => $consent['expiresAt'],
        ];
    }
}
