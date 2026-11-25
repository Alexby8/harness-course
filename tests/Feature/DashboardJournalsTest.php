<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\BotStatus;
use App\Enums\WebhookEventStatus;
use App\Livewire\Dashboard\ApiLogs;
use App\Livewire\Dashboard\WebhookEvents;
use App\Models\ApiRequestLog;
use App\Models\Bot;
use App\Models\User;
use App\Models\WebhookEvent;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

final class DashboardJournalsTest extends TestCase
{
    use RefreshDatabase;

    /** @return array<string, array{string}> */
    public static function privatePages(): array
    {
        return [
            'bots' => ['/dashboard'],
            'key' => ['/dashboard/api-key'],
            'api logs' => ['/dashboard/api-logs'],
            'webhooks' => ['/dashboard/webhook-events'],
        ];
    }

    #[DataProvider('privatePages')]
    public function test_pages_require_login_and_verified_email(string $path): void
    {
        $this->get($path)->assertRedirect(route('login'));
        $this->actingAs(User::factory()->unverified()->create())
            ->get($path)->assertRedirect(route('verification.notice'));
        $this->actingAs(User::factory()->create())->get($path)->assertOk();
    }

    public function test_api_logs_show_only_the_current_users_undeleted_bots(): void
    {
        $owner = User::factory()->create(['is_admin' => true]);
        $ownBot = $this->botFor($owner, 'bot_own');
        $foreignBot = $this->botFor(User::factory()->create(), 'bot_foreign');
        $deletedBot = $this->botFor($owner, 'bot_deleted');
        $this->apiLog($ownBot, ['method' => 'ownRequest']);
        $this->apiLog($foreignBot, ['method' => 'foreignRequest']);
        $this->apiLog($deletedBot, ['method' => 'deletedRequest']);
        $deletedBot->delete();

        $this->actingAs($owner)->get('/dashboard/api-logs')
            ->assertOk()->assertSee('ownRequest')->assertDontSee('foreignRequest')->assertDontSee('deletedRequest')
            ->assertDontSee('<th scope="col">Пользователь</th>', false);

        Livewire::test(ApiLogs::class)
            ->set('bot', $foreignBot->public_id)
            ->assertDontSee('ownRequest')->assertDontSee('foreignRequest')
            ->set('bot', $deletedBot->public_id)
            ->assertDontSee('deletedRequest');
    }

    public function test_webhook_events_are_scoped_to_owned_undeleted_bots_and_do_not_load_payloads(): void
    {
        $owner = User::factory()->create();
        $ownBot = $this->botFor($owner, 'bot_own');
        $foreignBot = $this->botFor(User::factory()->create(), 'bot_foreign');
        $deletedBot = $this->botFor($owner, 'bot_deleted');
        $this->event($ownBot, ['target_url' => 'https://owned.example.test/hook', 'payload' => ['text' => 'PRIVATE_PAYLOAD']]);
        $this->event($foreignBot, ['target_url' => 'https://foreign.example.test/hook']);
        $this->event($deletedBot, ['target_url' => 'https://deleted.example.test/hook']);
        $deletedBot->delete();

        $this->actingAs($owner);
        Livewire::test(WebhookEvents::class)
            ->assertSee('https://owned.example.test/hook')
            ->assertDontSee('https://foreign.example.test/hook')->assertDontSee('https://deleted.example.test/hook')
            ->assertDontSee('PRIVATE_PAYLOAD')
            ->assertViewHas('records', fn ($records): bool => ! array_key_exists('payload', $records->first()->getAttributes()))
            ->set('bot', $foreignBot->public_id)->assertDontSee('https://foreign.example.test/hook')
            ->assertDontSee('https://owned.example.test/hook');
    }

    public function test_api_error_filter_includes_connection_failures_and_can_isolate_them(): void
    {
        $owner = User::factory()->create();
        $bot = $this->botFor($owner, 'bot_results');
        $this->apiLog($bot, ['method' => 'successfulRequest', 'telegram_ok' => true]);
        $this->apiLog($bot, ['method' => 'rejectedRequest', 'telegram_ok' => false, 'error' => 'Telegram rejected']);
        $this->apiLog($bot, ['method' => 'connectionFailure', 'telegram_ok' => null, 'telegram_http_status' => null, 'error' => 'Ошибка соединения с Telegram']);
        $this->actingAs($owner);

        Livewire::test(ApiLogs::class)
            ->assertSee('<option value="error">Ошибка</option>', false)->assertDontSee('Все ошибки')
            ->set('status', 'error')->assertSee('rejectedRequest')->assertSee('connectionFailure')->assertDontSee('successfulRequest')
            ->set('status', 'no_response')->assertSee('connectionFailure')->assertSee('Нет ответа')->assertDontSee('rejectedRequest')
            ->set('status', 'success')->assertSee('successfulRequest')->assertDontSee('connectionFailure');
    }

    public function test_bot_status_and_inclusive_date_filters_work_together(): void
    {
        $owner = User::factory()->create();
        $selected = $this->botFor($owner, 'bot_selected');
        $other = $this->botFor($owner, 'bot_other');
        $this->event($selected, ['target_url' => 'https://end-of-day.test/hook', 'status' => WebhookEventStatus::Failed, 'created_at' => '2026-10-12 23:59:59']);
        $this->event($selected, ['target_url' => 'https://next-day.test/hook', 'status' => WebhookEventStatus::Failed, 'created_at' => '2026-10-13 00:00:00']);
        $this->event($selected, ['target_url' => 'https://sent.test/hook', 'status' => WebhookEventStatus::Sent, 'created_at' => '2026-10-12 10:00:00']);
        $this->event($other, ['target_url' => 'https://other-bot.test/hook', 'status' => WebhookEventStatus::Failed, 'created_at' => '2026-10-12 10:00:00']);
        $this->actingAs($owner);

        Livewire::test(WebhookEvents::class)
            ->set('bot', $selected->public_id)->set('status', 'failed')->set('dateFrom', '2026-10-12')->set('dateTo', '2026-10-12')
            ->assertSee('https://end-of-day.test/hook')->assertDontSee('https://next-day.test/hook')
            ->assertDontSee('https://sent.test/hook')->assertDontSee('https://other-bot.test/hook')
            ->call('resetFilters')->assertSet('bot', '')->assertSet('status', '')->assertSet('dateFrom', '')->assertSet('dateTo', '')
            ->assertSee('https://next-day.test/hook')->assertSee('https://other-bot.test/hook');
    }

    public function test_initial_url_filters_are_applied(): void
    {
        $owner = User::factory()->create();
        $bot = $this->botFor($owner, 'bot_selected');
        $this->apiLog($bot, ['method' => 'urlSelectedRequest', 'telegram_ok' => null]);
        $this->apiLog($bot, ['method' => 'urlExcludedRequest', 'telegram_ok' => true]);

        $this->actingAs($owner)->get('/dashboard/api-logs?bot=bot_selected&status=no_response&from=2026-10-01')
            ->assertOk()->assertSee('urlSelectedRequest')->assertDontSee('urlExcludedRequest');
    }

    public function test_pagination_is_stable_and_filter_changes_return_to_the_first_page(): void
    {
        $owner = User::factory()->create();
        $bot = $this->botFor($owner, 'bot_pages');
        for ($i = 1; $i <= 11; $i++) {
            $this->apiLog($bot, ['method' => sprintf('requestNumber%02d', $i), 'created_at' => '2026-10-12 12:00:00', 'duration_ms' => $i, 'telegram_ok' => $i === 11 ? null : true]);
        }
        $this->actingAs($owner);

        $this->get('/dashboard/api-logs?perPage=100&search=requestNumber11')
            ->assertOk()->assertSee('requestNumber11')->assertSee('requestNumber02')->assertDontSee('requestNumber01');

        Livewire::test(ApiLogs::class)
            ->assertViewHas('records', fn ($records): bool => $records->count() === 10)
            ->assertSee('requestNumber11')->assertSee('requestNumber02')->assertDontSee('requestNumber01')
            ->call('gotoPage', 2)->assertSee('requestNumber01')->assertDontSee('requestNumber11')
            ->set('status', 'no_response')->assertSee('requestNumber11')->assertSet('paginators.page', 1)
            ->call('resetFilters')->call('sortBy', 'duration_ms')->assertSee('requestNumber11')->assertSet('direction', 'desc')
            ->call('sortBy', 'duration_ms')->assertSee('requestNumber01')->assertDontSee('requestNumber11');
    }

    public function test_webhook_page_size_is_fixed_even_with_legacy_url_parameters(): void
    {
        $owner = User::factory()->create();
        $bot = $this->botFor($owner, 'bot_webhook_pages');
        for ($i = 1; $i <= 11; $i++) {
            $this->event($bot, ['target_url' => sprintf('https://example.test/event-%02d', $i), 'created_at' => '2026-10-12 12:00:00']);
        }

        $this->actingAs($owner)->get('/dashboard/webhook-events?perPage=100&search=event-11')
            ->assertOk()->assertSee('https://example.test/event-11')->assertSee('https://example.test/event-02')->assertDontSee('https://example.test/event-01');

        Livewire::test(WebhookEvents::class)
            ->assertViewHas('records', fn ($records): bool => $records->count() === 10)
            ->call('gotoPage', 2)->assertSee('https://example.test/event-01')->assertDontSee('https://example.test/event-11');
    }

    public function test_webhook_error_details_are_capped_and_escaped_without_changing_stored_errors(): void
    {
        $owner = User::factory()->create();
        $bot = $this->botFor($owner, 'bot_error_details');
        $readableError = str_repeat('x', 60).'ERROR_DETAIL_END';
        $longError = '<img src=x onerror=alert(1)>'.str_repeat('x', 1000).'HIDDEN_ERROR_TAIL';
        $this->event($bot, ['last_error' => null]);
        $this->event($bot, ['last_error' => $readableError]);
        $event = $this->event($bot, ['last_error' => $longError]);

        $this->actingAs($owner)->get('/dashboard/webhook-events')
            ->assertOk()->assertSee($readableError)
            ->assertSee('title="'.e(mb_substr($longError, 0, 1000).'...').'"', false)
            ->assertDontSee('HIDDEN_ERROR_TAIL')->assertDontSee('<img src=x onerror=alert(1)>', false);

        $this->assertDatabaseHas('webhook_events', ['id' => $event->id, 'last_error' => $longError]);
    }

    public function test_mutated_sort_and_filters_are_validated_before_querying(): void
    {
        $this->actingAs(User::factory()->create());
        Livewire::test(ApiLogs::class)->set('sort', 'user_id')->assertHasErrors(['sort']);
        Livewire::test(WebhookEvents::class)->set('status', 'invalid')->assertHasErrors(['status']);
        Livewire::test(ApiLogs::class)->set('dateFrom', '2026-10-12')->set('dateTo', '2026-10-01')
            ->assertHasErrors(['dateTo'])->assertSee('Конец периода должен быть не раньше его начала.')
            ->set('dateTo', '2026-10-12')->assertHasNoErrors();
    }

    /** @return array<string, array{class-string<ApiLogs>|class-string<WebhookEvents>}> */
    public static function journalComponents(): array
    {
        return ['api logs' => [ApiLogs::class], 'webhook events' => [WebhookEvents::class]];
    }

    #[DataProvider('journalComponents')]
    public function test_a_missing_page_is_recovered(string $component): void
    {
        $owner = User::factory()->create();
        $bot = $this->botFor($owner, 'bot_missing_page');
        $component === ApiLogs::class ? $this->apiLog($bot) : $this->event($bot);
        $this->actingAs($owner);

        Livewire::withQueryParams(['page' => 999])->test($component)
            ->assertViewHas('records', fn ($records): bool => $records->currentPage() === 1 && $records->count() === 1)
            ->assertSet('paginators.page', 1);
    }

    #[DataProvider('journalComponents')]
    public function test_refresh_recovers_a_page_after_old_records_are_removed(string $component): void
    {
        $owner = User::factory()->create();
        $bot = $this->botFor($owner, 'bot_pruned_page');
        $oldest = $component === ApiLogs::class ? $this->apiLog($bot) : $this->event($bot);
        for ($i = 0; $i < 10; $i++) {
            $component === ApiLogs::class ? $this->apiLog($bot) : $this->event($bot);
        }
        $this->actingAs($owner);
        $table = Livewire::test($component)->call('gotoPage', 2)
            ->assertViewHas('records', fn ($records): bool => $records->count() === 1);

        $oldest->delete();

        $table->call('$refresh')
            ->assertViewHas('records', fn ($records): bool => $records->currentPage() === 1 && $records->count() === 10)
            ->assertSet('paginators.page', 1);
    }

    #[DataProvider('journalComponents')]
    public function test_livewire_updates_recheck_access_and_bot_deletion(string $component): void
    {
        $owner = User::factory()->create();
        $bot = $this->botFor($owner, 'bot_access_change');
        $component === ApiLogs::class ? $this->apiLog($bot) : $this->event($bot);
        $this->actingAs($owner);
        $table = Livewire::test($component)
            ->assertViewHas('records', fn ($records): bool => $records->count() === 1);

        $bot->delete();
        $table->call('$refresh')->assertViewHas('records', fn ($records): bool => $records->count() === 0);

        $owner->forceFill(['email_verified_at' => null])->save();
        $this->actingAs($owner->fresh());
        $table->call('$refresh')->assertForbidden();
    }

    public function test_livewire_requests_recheck_email_verification(): void
    {
        $this->actingAs(User::factory()->unverified()->create());
        Livewire::test(ApiLogs::class)->assertForbidden();
        Livewire::test(WebhookEvents::class)->assertForbidden();
    }

    public function test_errors_and_urls_are_escaped_even_when_expanded(): void
    {
        $owner = User::factory()->create();
        $bot = $this->botFor($owner, 'bot_escape');
        $error = '<script>alert("private")</script>'.str_repeat('error ', 15);
        $this->apiLog($bot, ['error' => $error]);
        $this->event($bot, ['last_error' => $error, 'target_url' => 'https://example.test/<script>alert("url")</script>']);
        $this->actingAs($owner);

        Livewire::test(ApiLogs::class)->assertSee($error)->assertDontSee('<script>alert("private")</script>', false);
        Livewire::test(WebhookEvents::class)->assertSee($error)->assertDontSee('<script>alert("url")</script>', false);
    }

    public function test_api_key_is_moved_and_new_key_is_shown_only_once(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user)->get('/dashboard')->assertDontSee('Создать ключ');
        $this->get('/dashboard/api-key')->assertOk()->assertSee('Создать ключ');
        $response = $this->post('/dashboard/reissue-api-key')->assertRedirect(route('dashboard.api-key'));
        $key = session('new_api_key');
        $this->assertIsString($key);
        $this->assertSame(hash('sha256', $key), $user->fresh()->api_key_hash);
        $this->get($response->headers->get('Location'))->assertSee($key);
        $this->get('/dashboard/api-key')->assertDontSee($key);
    }

    public function test_bot_name_is_escaped_in_the_page_title(): void
    {
        Http::preventStrayRequests();
        $owner = User::factory()->create();
        $bot = $this->botFor($owner, 'bot_title');
        $name = '</title><script>window.auditTitle = 1</script>';
        $bot->update(['name' => $name]);

        $this->actingAs($owner)->get('/bots/'.$bot->public_id)
            ->assertOk()->assertSee($name)->assertDontSee($name, false);
    }

    private function botFor(User $user, string $publicId): Bot
    {
        return Bot::query()->create([
            'user_id' => $user->id,
            'public_id' => $publicId,
            'name' => 'Bot '.$publicId,
            'telegram_token_encrypted' => '123456789:local-test-placeholder',
            'telegram_token_hash' => hash('sha256', $publicId),
            'status' => BotStatus::Active,
        ]);
    }

    /** @param array<string, mixed> $attributes */
    private function apiLog(Bot $bot, array $attributes = []): ApiRequestLog
    {
        return ApiRequestLog::query()->forceCreate(array_merge([
            'user_id' => $bot->user_id, 'bot_id' => $bot->id, 'method' => 'sendMessage',
            'telegram_ok' => true, 'telegram_http_status' => 200, 'duration_ms' => 100,
        ], $attributes));
    }

    /** @param array<string, mixed> $attributes */
    private function event(Bot $bot, array $attributes = []): WebhookEvent
    {
        return WebhookEvent::query()->forceCreate(array_merge([
            'bot_id' => $bot->id, 'payload' => [], 'target_url' => 'https://example.test/hook',
            'status' => WebhookEventStatus::Pending, 'attempts' => 0,
        ], $attributes));
    }
}
