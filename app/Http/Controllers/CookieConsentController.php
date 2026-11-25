<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Services\AcquisitionTracker;
use App\Services\AnalyticsConsent;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Symfony\Component\HttpFoundation\Cookie;

final class CookieConsentController extends Controller
{
    public function __construct(
        private readonly AnalyticsConsent $consent,
        private readonly AcquisitionTracker $acquisition,
    ) {}

    public function show(Request $request): JsonResponse
    {
        return response()->json(['consent' => $this->consent->read($request)])
            ->header('Cache-Control', 'no-store, private');
    }

    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'choice' => ['required', Rule::in(['accepted', 'declined'])],
            'version' => ['required', Rule::in([Config::string('cookie-consent.version')])],
        ]);

        $consent = [
            'id' => (string) Str::uuid(),
            'version' => Config::string('cookie-consent.version'),
            'choice' => $request->string('choice')->toString(),
            'recordedAt' => now()->toIso8601String(),
            'expiresAt' => now()->addDays(Config::integer('cookie-consent.days'))->getTimestampMs(),
        ];

        // Record only the decision, not IP, user agent, account or analytics identifiers.
        // A failed audit write must not return a successful acceptance.
        Log::channel('cookie-consent')->info('cookie_consent', [
            ...$consent,
            'previousId' => $this->consent->read($request)['id'] ?? null,
        ]);

        $response = response()->json(['consent' => $consent])
            ->header('Cache-Control', 'no-store, private')
            // Symfony does not inherit SESSION_DOMAIN when domain is null.
            ->cookie(new Cookie(
                name: Config::string('cookie-consent.cookie'),
                value: json_encode($consent, JSON_THROW_ON_ERROR),
                expire: now()->addDays(Config::integer('cookie-consent.days')),
                path: '/',
                domain: null,
                secure: app()->isProduction() || $request->isSecure(),
                httpOnly: true,
                sameSite: 'lax',
            ));
        if ($consent['choice'] === 'declined') {
            $this->acquisition->clear($request, $response);
        }

        return $response;
    }
}
