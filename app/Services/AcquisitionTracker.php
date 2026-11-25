<?php

declare(strict_types=1);

namespace App\Services;

use App\DTO\AcquisitionTouch;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Config;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\Cookie;
use Symfony\Component\HttpFoundation\Response;

final class AcquisitionTracker
{
    public function __construct(private readonly AnalyticsConsent $consent) {}

    public function allowed(Request $request): bool
    {
        return ($this->consent->read($request)['choice'] ?? null) !== 'declined';
    }

    public function capture(Request $request, Response $response): void
    {
        if (! $this->allowed($request) || ! $request->boolean('analytics_allowed')) {
            $this->clear($request, $response);

            return;
        }

        // Existing accounts retain their registration source; later visits cannot overwrite it.
        if ($request->user() !== null) {
            return;
        }

        if ($request->has('parameters')) {
            $parameters = [];
            foreach (Config::array('acquisition.parameters') as $key => $limit) {
                $value = $request->input('parameters.'.$key);
                if (is_string($value) && $value !== '' && is_int($limit)) {
                    $parameters[$key] = mb_strcut(trim($value), 0, $limit, 'UTF-8');
                }
            }
            $touch = new AcquisitionTouch(
                $parameters,
                mb_strcut($request->string('landing_path')->toString(), 0, 160, 'UTF-8'),
                $request->filled('referrer_host') ? $request->string('referrer_host')->toString() : null,
                now()->getTimestamp(),
            );
            $encoded = json_encode($touch->toArray(), JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            // Reject oversized metadata rather than issuing an unusable browser cookie.
            if (strlen($encoded) > 1400) {
                throw ValidationException::withMessages(['parameters' => 'Слишком большой набор рекламных меток.']);
            }
            if ($this->readTouch($request, 'first') === null) {
                $response->headers->setCookie($this->cookie($request, 'first', $encoded));
            }
            if ($parameters !== []) {
                $response->headers->setCookie($this->cookie($request, 'last', $encoded));
            }
        }

        if ($request->filled('client_id')) {
            $response->headers->setCookie($this->cookie($request, 'client', json_encode([
                'id' => $request->string('client_id')->toString(), 'captured_at' => now()->getTimestamp(),
            ], JSON_THROW_ON_ERROR)));
        }
    }

    public function attach(Request $request, User $user): void
    {
        // Default false also protects no-JavaScript submissions and a failed consent check.
        if (! $request->boolean('analytics_allowed') || ! $this->allowed($request)) {
            return;
        }

        $first = $this->readTouch($request, 'first');
        $last = $this->readTouch($request, 'last');
        $client = $this->read($request, 'client');
        $clientId = is_string($client['id'] ?? null) && preg_match('/\A[0-9]{1,64}\z/D', $client['id']) ? $client['id'] : null;
        if ($first !== null || $last !== null || $clientId !== null) {
            $user->acquisition()->create([
                'first_touch' => $first,
                'last_tagged_touch' => $last,
                'metrika_client_id' => $clientId,
            ]);
        }
    }

    public function clear(Request $request, Response $response): void
    {
        $this->forgetCookies($request, $response);
        $user = $request->user();
        if ($user instanceof User) {
            $user->acquisition()->delete();
        }
    }

    public function forgetCookies(Request $request, Response $response): void
    {
        foreach (['first', 'last', 'client'] as $kind) {
            $response->headers->setCookie($this->cookie($request, $kind, '', true));
        }
    }

    /** @return array<string, mixed>|null */
    private function read(Request $request, string $kind): ?array
    {
        $value = $request->cookie(Config::string('acquisition.cookies.'.$kind));
        $data = is_string($value) ? json_decode($value, true) : null;
        if (! is_array($data) || ! is_int($data['captured_at'] ?? null)
            || $data['captured_at'] > now()->getTimestamp()
            || $data['captured_at'] <= now()->subDays(Config::integer('acquisition.days'))->getTimestamp()) {
            return null;
        }

        return $data;
    }

    /** @return array<string, mixed>|null */
    private function readTouch(Request $request, string $kind): ?array
    {
        $data = $this->read($request, $kind);

        return is_array($data['parameters'] ?? null) && is_string($data['landing_path'] ?? null) ? $data : null;
    }

    private function cookie(Request $request, string $kind, string $value, bool $forget = false): Cookie
    {
        return new Cookie(
            name: Config::string('acquisition.cookies.'.$kind), value: $value,
            expire: $forget ? now()->subYear() : now()->addDays(Config::integer('acquisition.days')),
            path: '/', domain: null,
            secure: app()->isProduction() || $request->isSecure(), httpOnly: true, sameSite: 'lax',
        );
    }
}
