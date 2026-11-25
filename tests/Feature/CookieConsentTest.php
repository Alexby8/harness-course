<?php

declare(strict_types=1);

namespace Tests\Feature;

use Illuminate\Cookie\CookieValuePrefix;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Mockery;
use Psr\Log\LoggerInterface;
use Tests\TestCase;

final class CookieConsentTest extends TestCase
{
    use RefreshDatabase;

    private const array PUBLIC_PATHS = [
        '/', '/docs', '/guides',
        '/guides/telegram-bot-api-timeout',
        '/guides/telegram-webhook-not-working',
        '/guides/telegram-bot-api-proxy',
        '/guides/laravel-telegram-notifications',
        '/guides/php-telegram-bot-sdk',
        '/guides/telegram-bot-api-error-429',
    ];

    public function test_frontend_has_choice_and_no_unconditional_counter_or_pixel(): void
    {
        $this->app['env'] = 'production';
        config(['botgate.yandex_metrika_id' => '123456']);

        foreach (self::PUBLIC_PATHS as $url) {
            $this->get($url)->assertOk()
                ->assertSee('data-cookie-consent', false)
                ->assertSee('Согласен')->assertSee('Настройки')->assertSee('Отключить Метрику и рекламную атрибуцию')
                ->assertSee('Используем cookies для работы сайта и анализа посещаемости.')
                ->assertDontSee('data-cookie-close', false)
                ->assertSee('/documents/BotGate_Analytics_Consent.pdf', false)
                ->assertDontSee('mc.yandex.ru', false)
                ->assertDontSee('webvisor:true', false)
                ->assertSee('data-metrika-id="123456"', false);
        }

        foreach (['/login', '/register', '/forgot-password', '/reset-password/example'] as $url) {
            $this->get($url)->assertOk()
                ->assertDontSee('data-cookie-consent', false)
                ->assertDontSee('data-cookie-open', false)
                ->assertDontSee('mc.yandex.ru', false);
        }
    }

    public function test_counter_is_disabled_outside_production_even_with_an_id(): void
    {
        config(['botgate.yandex_metrika_id' => '123456']);
        foreach (self::PUBLIC_PATHS as $url) {
            $this->get($url)->assertOk()->assertSee('data-metrika-id=""', false);
        }
    }

    public function test_no_choice_is_inferred_for_a_new_visitor(): void
    {
        $this->getJson('/cookie-consent')->assertOk()->assertExactJson(['consent' => null])
            ->assertHeader('Cache-Control', 'no-store, private');
    }

    public function test_acceptance_is_recorded_and_saved_in_a_protected_cookie(): void
    {
        $this->freezeTime();
        $logger = Mockery::mock(LoggerInterface::class);
        $logger->shouldReceive('info')->once()->with('cookie_consent', Mockery::on(
            fn (array $context): bool => $context['choice'] === 'accepted'
                && $context['version'] === config('cookie-consent.version')
                && Str::isUuid($context['id'])
                && ! isset($context['ip'], $context['email'], $context['user_agent']),
        ));
        Log::shouldReceive('channel')->with('cookie-consent')->once()->andReturn($logger);

        $response = $this->postJson('https://localhost/cookie-consent', [
            'choice' => 'accepted', 'version' => config('cookie-consent.version'),
        ])->assertOk()->assertJsonPath('consent.choice', 'accepted')
            ->assertJsonPath('consent.expiresAt', now()->addDays(180)->getTimestampMs());

        $cookie = collect($response->headers->getCookies())->first(
            fn ($cookie): bool => $cookie->getName() === config('cookie-consent.cookie'),
        );
        $this->assertNotNull($cookie);
        $this->assertTrue($cookie->isHttpOnly());
        $this->assertTrue($cookie->isSecure());
        $this->assertSame('lax', $cookie->getSameSite());
        $this->assertSame('/', $cookie->getPath());
        $this->assertStringNotContainsString('accepted', $cookie->getValue());

        $this->withCredentials()->withCookie(config('cookie-consent.cookie'), json_encode($response->json('consent')))
            ->getJson('/cookie-consent')->assertOk()
            ->assertJsonPath('consent.id', $response->json('consent.id'))
            ->assertJsonPath('consent.choice', 'accepted');
    }

    public function test_withdrawal_is_linked_to_the_previous_decision(): void
    {
        $previous = [
            'id' => (string) Str::uuid(), 'choice' => 'accepted',
            'version' => config('cookie-consent.version'),
            'expiresAt' => now()->addDay()->getTimestampMs(),
        ];
        $logger = Mockery::mock(LoggerInterface::class);
        $logger->shouldReceive('info')->once()->with('cookie_consent', Mockery::on(
            fn (array $context): bool => $context['choice'] === 'declined' && $context['previousId'] === $previous['id'],
        ));
        Log::shouldReceive('channel')->with('cookie-consent')->once()->andReturn($logger);

        $this->withCredentials()->withCookie(config('cookie-consent.cookie'), json_encode($previous))
            ->postJson('/cookie-consent', ['choice' => 'declined', 'version' => config('cookie-consent.version')])
            ->assertOk()->assertJsonPath('consent.choice', 'declined');
    }

    public function test_invalid_expired_and_old_acceptances_are_not_recorded_as_current_consent(): void
    {
        $valid = [
            'id' => (string) Str::uuid(), 'choice' => 'accepted',
            'version' => config('cookie-consent.version'), 'expiresAt' => now()->addDay()->getTimestampMs(),
        ];
        foreach ([
            'broken',
            json_encode([...$valid, 'expiresAt' => now()->subSecond()->getTimestampMs()]),
            json_encode([...$valid, 'version' => 'obsolete']),
            json_encode([...$valid, 'choice' => 'anything']),
        ] as $cookie) {
            $this->withCredentials()->withCookie(config('cookie-consent.cookie'), $cookie)
                ->getJson('/cookie-consent')->assertOk()->assertExactJson(['consent' => null]);
        }
    }

    public function test_previous_version_refusal_is_preserved(): void
    {
        $previous = [
            'id' => (string) Str::uuid(), 'choice' => 'declined',
            'version' => '2026-09-26', 'expiresAt' => now()->addDay()->getTimestampMs(),
        ];

        $this->withCredentials()->withCookie(config('cookie-consent.legacy_cookie'), json_encode($previous))
            ->getJson('/cookie-consent')->assertOk()->assertExactJson(['consent' => $previous]);
    }

    public function test_new_cookie_does_not_inherit_session_domain(): void
    {
        Log::shouldReceive('channel')->with('cookie-consent')->andReturnSelf();
        Log::shouldReceive('info')->once();
        $this->app['cookie']->setDefaultPathAndDomain('/', '.bot-gate.ru');
        $response = $this->postJson('https://dev.bot-gate.ru/cookie-consent', [
            'choice' => 'declined', 'version' => config('cookie-consent.version'),
        ])->assertOk();

        $cookie = collect($response->headers->getCookies())->first(
            fn ($cookie): bool => $cookie->getName() === config('cookie-consent.cookie'),
        );
        $this->assertNotNull($cookie);
        $this->assertNull($cookie->getDomain());
        $this->assertNotSame(config('cookie-consent.legacy_cookie'), $cookie->getName());
        $this->assertSame('/', $cookie->getPath());
        $this->assertTrue($cookie->isSecure());
        $this->assertTrue($cookie->isHttpOnly());

        // Replay the actual encrypted Set-Cookie value, rather than synthesizing JSON.
        $this->withCredentials()->withUnencryptedCookie($cookie->getName(), $cookie->getValue())
            ->getJson('https://dev.bot-gate.ru/cookie-consent')
            ->assertOk()->assertJsonPath('consent.id', $response->json('consent.id'));
    }

    public function test_readable_legacy_refusal_survives_a_shadowing_cookie(): void
    {
        $legacyName = config('cookie-consent.legacy_cookie');
        $previous = [
            'id' => (string) Str::uuid(), 'choice' => 'declined',
            'version' => '2026-09-26', 'expiresAt' => now()->addDay()->getTimestampMs(),
        ];
        $encrypted = Crypt::encryptString(CookieValuePrefix::create($legacyName, Crypt::getKey()).json_encode($previous));
        $header = $legacyName.'=unreadable; '.$legacyName.'='.rawurlencode($encrypted);

        $this->withCredentials()->withUnencryptedCookie($legacyName, 'unreadable')
            ->getJson('/cookie-consent', ['Cookie' => $header])
            ->assertOk()->assertExactJson(['consent' => $previous]);

        Log::shouldReceive('channel')->with('cookie-consent')->andReturnSelf();
        Log::shouldReceive('info')->once();
        $response = $this->postJson('/cookie-consent', [
            'choice' => 'accepted', 'version' => config('cookie-consent.version'),
        ])->assertOk();
        $newValue = collect($response->headers->getCookies())->first(
            fn ($cookie): bool => $cookie->getName() === config('cookie-consent.cookie'),
        )->getValue();
        $this->withUnencryptedCookie(config('cookie-consent.cookie'), $newValue)
            ->getJson('/cookie-consent', ['Cookie' => $header])
            ->assertOk()->assertJsonPath('consent.choice', 'accepted')
            ->assertJsonPath('consent.id', $response->json('consent.id'));
    }

    public function test_legacy_cookie_requires_the_correct_encrypted_name_prefix(): void
    {
        $legacyName = config('cookie-consent.legacy_cookie');
        $previous = [
            'id' => (string) Str::uuid(), 'choice' => 'declined',
            'version' => '2026-09-26', 'expiresAt' => now()->addDay()->getTimestampMs(),
        ];
        $encrypted = Crypt::encryptString(CookieValuePrefix::create('different-cookie', Crypt::getKey()).json_encode($previous));
        $this->getJson('/cookie-consent', ['Cookie' => $legacyName.'='.rawurlencode($encrypted)])
            ->assertOk()->assertExactJson(['consent' => null]);
    }

    public function test_only_explicit_choices_for_the_current_version_are_accepted(): void
    {
        foreach ([[], ['choice' => 'yes'], ['choice' => 'accepted', 'version' => 'obsolete']] as $payload) {
            $this->postJson('/cookie-consent', $payload)->assertUnprocessable()
                ->assertCookieMissing(config('cookie-consent.cookie'));
        }
    }

    public function test_failed_audit_write_cannot_grant_consent(): void
    {
        $logger = Mockery::mock(LoggerInterface::class);
        $logger->shouldReceive('info')->once()->andThrow(new \RuntimeException('Audit unavailable'));
        Log::shouldReceive('channel')->with('cookie-consent')->once()->andReturn($logger);
        $this->withoutExceptionHandling();
        $this->expectException(\RuntimeException::class);
        $this->postJson('/cookie-consent', ['choice' => 'accepted', 'version' => config('cookie-consent.version')]);
    }
}
