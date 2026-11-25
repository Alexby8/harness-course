<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\User;
use App\Models\UserAcquisition;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Str;
use Illuminate\Testing\TestResponse;
use Tests\TestCase;

final class AcquisitionTest extends TestCase
{
    use RefreshDatabase;

    /** @param array<string, string> $parameters */
    private function capture(array $parameters = [], ?string $clientId = null): TestResponse
    {
        return $this->withCredentials()->postJson('/acquisition', [
            'analytics_allowed' => true, 'parameters' => $parameters,
            'landing_path' => '/docs', 'referrer_host' => 'yandex.ru', 'client_id' => $clientId,
        ])->assertOk();
    }

    private function rememberCookies(TestResponse $response): void
    {
        foreach ($response->headers->getCookies() as $cookie) {
            if (str_starts_with($cookie->getName(), 'botgate_acquisition_')) {
                $this->withUnencryptedCookie($cookie->getName(), $cookie->getValue());
            }
        }
    }

    private function register(bool $allowed = true): TestResponse
    {
        Notification::fake();

        return $this->post('/register', [
            'email' => 'new@example.test', 'password' => 'password123', 'password_confirmation' => 'password123',
            'terms' => '1', 'analytics_allowed' => $allowed ? '1' : '0',
        ])->assertRedirect('/email/verify');
    }

    private function refuse(): void
    {
        $this->withCookie(config('cookie-consent.cookie'), json_encode([
            'id' => (string) Str::uuid(), 'version' => 'older-version', 'choice' => 'declined',
            'expiresAt' => now()->addDay()->getTimestampMs(),
        ], JSON_THROW_ON_ERROR));
    }

    public function test_cookie_metadata_and_client_id_are_attached_only_at_registration(): void
    {
        $this->freezeTime();
        $parameters = [
            'yclid' => '12345678901234567890', 'utm_source' => 'yandex', 'utm_medium' => 'cpc',
            'utm_campaign' => '123', 'utm_content' => '456', 'utm_term' => 'телеграм бот',
            'group_id' => '7', 'phrase_id' => '8', 'source_type' => 'search',
            'placement' => 'none', 'device_type' => 'mobile', 'region_id' => '213',
        ];
        $response = $this->capture($parameters, '9876543210987654321');
        $this->assertDatabaseCount('user_acquisitions', 0);
        $this->rememberCookies($response);
        $registration = $this->register();
        $row = UserAcquisition::query()->sole();
        $this->assertSame($parameters, $row->first_touch['parameters']);
        $this->assertSame($parameters, $row->last_tagged_touch['parameters']);
        $this->assertSame('/docs', $row->first_touch['landing_path']);
        $this->assertSame('yandex.ru', $row->first_touch['referrer_host']);
        $this->assertSame(now()->getTimestamp(), $row->first_touch['captured_at']);
        $this->assertSame('9876543210987654321', $row->metrika_client_id);
        $this->assertSame(User::query()->sole()->id, $row->user_id);
        $registration->assertCookieExpired(config('acquisition.cookies.first'));
    }

    public function test_first_touch_is_kept_and_last_touch_does_not_mix_parameters(): void
    {
        $this->rememberCookies($this->capture(['yclid' => '111', 'utm_campaign' => 'first']));
        $this->travel(1)->days();
        $this->rememberCookies($this->capture(['utm_source' => 'telegram']));
        $this->rememberCookies($this->capture());
        $this->register();
        $row = UserAcquisition::query()->sole();
        $this->assertSame('111', $row->first_touch['parameters']['yclid']);
        $this->assertSame(['utm_source' => 'telegram'], $row->last_tagged_touch['parameters']);
    }

    public function test_direct_first_visit_is_distinct_from_a_later_ad_click(): void
    {
        $this->rememberCookies($this->capture());
        $this->rememberCookies($this->capture(['yclid' => '222']));
        $this->register();
        $row = UserAcquisition::query()->sole();
        $this->assertSame([], $row->first_touch['parameters']);
        $this->assertSame(['yclid' => '222'], $row->last_tagged_touch['parameters']);
    }

    public function test_client_id_can_arrive_after_the_initial_capture(): void
    {
        $this->rememberCookies($this->capture(['yclid' => '333']));
        $response = $this->withCredentials()->postJson('/acquisition', [
            'analytics_allowed' => true, 'client_id' => '999',
        ])->assertOk();
        $this->rememberCookies($response);
        $this->register();
        $this->assertSame('999', UserAcquisition::query()->sole()->metrika_client_id);
    }

    public function test_refusal_blocks_capture_and_registration_even_with_existing_cookies(): void
    {
        $this->rememberCookies($this->capture(['yclid' => '444'], '999'));
        $this->refuse();
        $this->capture(['yclid' => '555'])->assertJsonPath('stored', false)
            ->assertCookieExpired(config('acquisition.cookies.first'));
        $this->register();
        $this->assertDatabaseCount('users', 1);
        $this->assertDatabaseCount('user_acquisitions', 0);
    }

    public function test_local_browser_refusal_flag_blocks_attachment(): void
    {
        $this->rememberCookies($this->capture(['yclid' => '444']));
        $this->register(false);
        $this->assertDatabaseCount('user_acquisitions', 0);
    }

    public function test_registration_without_javascript_or_cookies_still_works(): void
    {
        Notification::fake();
        $this->post('/register', [
            'email' => 'plain@example.test', 'password' => 'password123', 'password_confirmation' => 'password123', 'terms' => '1',
        ])->assertRedirect('/email/verify');
        $this->assertDatabaseCount('users', 1);
        $this->assertDatabaseCount('user_acquisitions', 0);
    }

    public function test_invalid_registration_preserves_cookies_for_retry(): void
    {
        $this->rememberCookies($this->capture(['yclid' => '666']));
        $this->post('/register', ['email' => 'bad'])->assertSessionHasErrors()
            ->assertCookieMissing(config('acquisition.cookies.first'));
        $this->register();
        $this->assertDatabaseCount('user_acquisitions', 1);
    }

    public function test_expired_and_tampered_cookies_are_ignored(): void
    {
        $this->rememberCookies($this->capture(['yclid' => '777'], '999'));
        $this->travel(31)->days();
        $this->withUnencryptedCookie(config('acquisition.cookies.first'), 'tampered');
        $this->register();
        $this->assertDatabaseCount('user_acquisitions', 0);
    }

    public function test_new_refusal_removes_the_authenticated_users_acquisition(): void
    {
        $user = User::factory()->create();
        $user->acquisition()->create(['metrika_client_id' => '999']);
        $this->actingAs($user)->postJson('/cookie-consent', [
            'choice' => 'declined', 'version' => config('cookie-consent.version'),
        ])->assertOk()->assertCookieExpired(config('acquisition.cookies.client'));
        $this->assertDatabaseCount('user_acquisitions', 0);
        $this->assertDatabaseCount('users', 1);
    }

    public function test_local_refusal_can_clear_only_the_current_accounts_row(): void
    {
        $user = User::factory()->create();
        $other = User::factory()->create();
        $user->acquisition()->create(['metrika_client_id' => '999']);
        $other->acquisition()->create(['metrika_client_id' => '888']);
        $this->actingAs($user)->deleteJson('/acquisition', ['withdrawal' => true, 'user_id' => $other->id])->assertOk();
        $this->assertDatabaseMissing('user_acquisitions', ['user_id' => $user->id]);
        $this->assertDatabaseHas('user_acquisitions', ['user_id' => $other->id]);
    }

    public function test_a_failed_browser_check_clears_cookies_without_deleting_account_history(): void
    {
        $user = User::factory()->create();
        $user->acquisition()->create(['metrika_client_id' => '999']);
        $this->actingAs($user)->deleteJson('/acquisition', ['withdrawal' => false])->assertOk()
            ->assertCookieExpired(config('acquisition.cookies.first'));
        $this->assertDatabaseCount('user_acquisitions', 1);
    }

    public function test_refusal_is_enforced_on_server_rendered_pages_without_javascript(): void
    {
        $user = User::factory()->create();
        $user->acquisition()->create(['metrika_client_id' => '999']);
        $this->refuse();
        $this->actingAs($user)->get('/')->assertOk()->assertCookieExpired(config('acquisition.cookies.first'));
        $this->assertDatabaseCount('user_acquisitions', 0);
    }

    public function test_capture_cannot_overwrite_an_existing_accounts_source(): void
    {
        $user = User::factory()->create();
        $user->acquisition()->create(['metrika_client_id' => '999']);
        $this->actingAs($user);
        $this->capture(['yclid' => '888'])->assertCookieMissing(config('acquisition.cookies.first'));
        $this->assertSame('999', $user->acquisition()->sole()->metrika_client_id);
    }

    public function test_only_allowed_bounded_metadata_and_numeric_client_ids_are_accepted(): void
    {
        foreach ([
            ['parameters' => ['email' => 'private@example.test']],
            ['parameters' => ['utm_term' => ['array']]],
            ['parameters' => ['utm_term' => str_repeat('a', 257)]],
            ['landing_path' => '/?token=secret'],
            ['landing_path' => '//external.test'],
            ['referrer_host' => 'https://example.test/?secret=1'],
            ['client_id' => '1.23e+10'],
        ] as $invalid) {
            $this->postJson('/acquisition', array_replace([
                'analytics_allowed' => true, 'parameters' => [], 'landing_path' => '/',
            ], $invalid))->assertUnprocessable()->assertCookieMissing(config('acquisition.cookies.first'));
        }
        $this->postJson('/acquisition', ['parameters' => [], 'landing_path' => '/'])->assertUnprocessable();
    }

    public function test_cookies_are_encrypted_host_only_secure_and_bounded(): void
    {
        $this->freezeTime();
        $this->app['cookie']->setDefaultPathAndDomain('/', '.bot-gate.ru');
        $response = $this->withCredentials()->postJson('https://dev.bot-gate.ru/acquisition', [
            'analytics_allowed' => true, 'parameters' => ['utm_term' => str_repeat('я', 256), 'yclid' => '123'],
            'landing_path' => '/', 'client_id' => '999',
        ])->assertOk();
        $bytes = 0;
        foreach ($response->headers->getCookies() as $cookie) {
            if (! str_starts_with($cookie->getName(), 'botgate_acquisition_')) {
                continue;
            }
            $this->assertNull($cookie->getDomain());
            $this->assertSame('/', $cookie->getPath());
            $this->assertTrue($cookie->isSecure());
            $this->assertTrue($cookie->isHttpOnly());
            $this->assertSame('lax', $cookie->getSameSite());
            $this->assertSame(now()->addDays(30)->getTimestamp(), $cookie->getExpiresTime());
            $this->assertLessThan(4096, strlen((string) $cookie));
            $this->assertStringNotContainsString('utm_term', $cookie->getValue());
            $bytes += strlen((string) $cookie);
        }
        $this->assertLessThan(6500, $bytes);
        $this->rememberCookies($response);
        $this->register();
        $this->assertSame(str_repeat('я', 128), UserAcquisition::query()->sole()->first_touch['parameters']['utm_term']);
    }

    public function test_oversized_combined_metadata_cannot_create_unusable_cookies(): void
    {
        $parameters = array_fill_keys(array_keys(config('acquisition.parameters')), str_repeat('x', 256));
        $this->postJson('/acquisition', [
            'analytics_allowed' => true, 'parameters' => $parameters,
            'landing_path' => '/'.str_repeat('a', 159), 'referrer_host' => str_repeat('a', 120),
        ])->assertUnprocessable()->assertCookieMissing(config('acquisition.cookies.first'));
        $this->register(false);
        $this->assertDatabaseCount('user_acquisitions', 0);
    }
}
