<?php

declare(strict_types=1);

namespace Tests\Feature\Filament;

use App\DTO\AdvertisingSource;
use App\Enums\AcquisitionChannel;
use App\Enums\BotStatus;
use App\Filament\Pages\Advertising;
use App\Filament\Widgets\AdvertisingOverview;
use App\Models\Bot;
use App\Models\BotDailyStat;
use App\Models\User;
use App\Models\UserAcquisition;
use App\Services\AdvertisingAnalytics;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Livewire\Livewire;
use Tests\TestCase;

final class AdvertisingTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->travelTo(CarbonImmutable::parse('2026-11-02 12:00:00'));
    }

    public function test_advertising_page_requires_an_admin_including_livewire_updates(): void
    {
        $this->get('/admin/advertising')->assertRedirect('/admin/login');
        $this->actingAs(User::factory()->create(['is_admin' => false]))->get('/admin/advertising')->assertForbidden();
        Livewire::test(Advertising::class)->assertForbidden();

        $admin = User::factory()->create(['is_admin' => true]);
        $this->actingAs($admin)->get('/admin/advertising')->assertOk()
            ->assertSee('Реклама')->assertSee('Воронка выбранных пользователей')
            ->assertSee('Срок не прошёл')->assertDontSee('data-metrika-id');
        $page = Livewire::test(Advertising::class);
        $admin->update(['is_admin' => false]);
        $page->call('$refresh')->assertForbidden();
    }

    public function test_channel_classification_uses_one_touch_and_keeps_unknown_separate(): void
    {
        $direct = new UserAcquisition(['last_tagged_touch' => ['parameters' => ['yclid' => '123']]]);
        $this->assertSame(AcquisitionChannel::YandexDirect, AdvertisingSource::fromAcquisition($direct)->channel);
        $utm = new UserAcquisition(['first_touch' => ['parameters' => ['utm_source' => 'Yandex', 'utm_medium' => 'CPC']]]);
        $this->assertSame(AcquisitionChannel::YandexDirect, AdvertisingSource::fromAcquisition($utm)->channel);
        $social = new UserAcquisition(['first_touch' => ['parameters' => ['utm_source' => 'vk', 'utm_medium' => 'paid_social']]]);
        $this->assertSame(AcquisitionChannel::OtherAdvertising, AdvertisingSource::fromAcquisition($social)->channel);
        $this->assertSame(AcquisitionChannel::Unknown, AdvertisingSource::fromAcquisition(null)->channel);
        $this->assertSame(AcquisitionChannel::Unknown, AdvertisingSource::fromAcquisition(new UserAcquisition(['metrika_client_id' => '123']))->channel);

        $mixed = new UserAcquisition([
            'first_touch' => ['parameters' => ['yclid' => '123', 'utm_campaign' => 'old']],
            'last_tagged_touch' => ['parameters' => ['utm_source' => 'telegram']],
        ]);
        $source = AdvertisingSource::fromAcquisition($mixed);
        $this->assertSame(AcquisitionChannel::OtherSources, $source->channel);
        $this->assertSame('', $source->campaign);
        $referral = new UserAcquisition(['first_touch' => ['parameters' => [], 'referrer_host' => 'example.test']]);
        $this->assertSame(AcquisitionChannel::OtherSources, AdvertisingSource::fromAcquisition($referral)->channel);
    }

    public function test_usage_counts_unique_owners_and_preserves_deleted_bot_history(): void
    {
        $owner = $this->user('2026-10-10', '101');
        $first = $this->bot($owner);
        $second = $this->bot($owner);
        $this->stat($first, '2026-10-11', ['api_requests_count' => 5, 'work_api_requests_count' => 5]);
        $this->stat($second, '2026-10-18', ['webhook_sent_count' => 4]);
        $this->stat($first, '2026-11-02', ['work_api_requests_count' => 1]);
        $this->stat($first, '2026-11-03', ['work_api_requests_count' => 100]);
        $first->update(['status' => BotStatus::Disabled]);
        $first->delete();

        $checkOnly = $this->user('2026-10-10', '101');
        $this->stat($this->bot($checkOnly), '2026-10-18', ['api_requests_count' => 1]);
        $failed = $this->user('2026-10-10', '101');
        $this->stat($this->bot($failed), '2026-10-18', ['api_requests_count' => 5, 'api_errors_count' => 5, 'webhook_received_count' => 1, 'webhook_failed_count' => 1]);
        $admin = $this->user('2026-10-10', '101');
        $admin->update(['is_admin' => true]);
        $this->stat($this->bot($admin), '2026-10-18', ['work_api_requests_count' => 10]);
        $this->user('2026-09-01', '101'); // Outside selected registration period.

        $report = app(AdvertisingAnalytics::class)->report();
        $totals = $report->summary($report->select());
        $this->assertSame(3, $totals['registered']);
        $this->assertSame(1, $totals['used']);
        $this->assertSame(1, $totals['retained']);
        $this->assertSame(1, $totals['active']);
        $this->assertSame(0, $totals['usage_unknown']);
        $row = $report->users->firstWhere('id', $owner->id);
        $this->assertSame(2, $row->bots);
        $this->assertTrue($row->retained);
    }

    public function test_retention_waits_for_full_second_week_and_respects_calendar_boundaries(): void
    {
        $ready = $this->user('2026-10-19', '101');
        $bot = $this->bot($ready);
        $this->stat($bot, '2026-10-25', ['work_api_requests_count' => 1]); // Day 7: excluded.
        $this->stat($bot, '2026-11-01', ['work_api_requests_count' => 1]); // Day 14: included.
        $pending = $this->user('2026-10-20', '101');
        $this->stat($this->bot($pending), '2026-10-27', ['work_api_requests_count' => 1]);
        $after = $this->user('2026-10-19', '101');
        $this->stat($this->bot($after), '2026-11-02', ['work_api_requests_count' => 1]); // Day 15: excluded.

        $report = app(AdvertisingAnalytics::class)->report();
        $this->assertTrue($report->users->firstWhere('id', $ready->id)->retained);
        $this->assertNull($report->users->firstWhere('id', $pending->id)->retained);
        $this->assertFalse($report->users->firstWhere('id', $pending->id)->retentionMatured);
        $this->assertFalse($report->users->firstWhere('id', $after->id)->retained);
        $totals = $report->summary($report->select());
        $this->assertSame(2, $totals['eligible']);
        $this->assertSame(1, $totals['pending']);
        $this->assertSame(1, $totals['retained']);
    }

    public function test_historical_api_days_are_unknown_but_a_successful_delivery_proves_usage(): void
    {
        $unknown = $this->user('2026-10-10', '101');
        $this->stat($this->bot($unknown), '2026-10-18', ['api_requests_count' => 5, 'work_api_requests_count' => null]);
        $known = $this->user('2026-10-10', '101');
        $bot = $this->bot($known);
        $this->stat($bot, '2026-10-18', ['api_requests_count' => 5, 'work_api_requests_count' => null]);
        $this->stat($bot, '2026-10-19', ['webhook_sent_count' => 1]);
        $errors = $this->user('2026-10-10', '101');
        $this->stat($this->bot($errors), '2026-10-18', ['api_requests_count' => 5, 'api_errors_count' => 5, 'work_api_requests_count' => null]);

        $report = app(AdvertisingAnalytics::class)->report();
        $this->assertNull($report->users->firstWhere('id', $unknown->id)->used);
        $this->assertNull($report->users->firstWhere('id', $unknown->id)->retained);
        $this->assertTrue($report->users->firstWhere('id', $known->id)->used);
        $this->assertTrue($report->users->firstWhere('id', $known->id)->retained);
        $this->assertFalse($report->users->firstWhere('id', $errors->id)->used);
        $totals = $report->summary($report->select());
        $this->assertSame(1, $totals['usage_unknown']);
        $this->assertSame(1, $totals['retention_unknown']);
    }

    public function test_source_withdrawal_removes_the_user_from_advertising_and_weekly_totals_reconcile(): void
    {
        $user = $this->user('2026-10-25', '101');
        $this->user('2026-11-02', null);
        $this->user('2026-11-02', null)->acquisition()->create(['first_touch' => ['parameters' => ['utm_source' => 'telegram']]]);
        $report = app(AdvertisingAnalytics::class)->report();
        $this->assertCount(1, $report->select());
        $this->assertSame(3, array_sum(array_column($report->weeks(), 'total')));
        $this->assertSame(1, array_sum(array_column($report->weeks(), 'paid')));
        $this->assertSame(1, array_sum(array_column($report->weeks(), 'other')));
        $this->assertSame(1, array_sum(array_column($report->weeks(), 'unknown')));

        $user->acquisition()->delete();
        $report = app(AdvertisingAnalytics::class)->report();
        $this->assertCount(0, $report->select());
        $this->assertCount(2, $report->select('unknown'));
    }

    public function test_page_filters_campaigns_stages_search_and_pagination_without_exposing_other_users(): void
    {
        $this->actingAs(User::factory()->create(['is_admin' => true]));
        $first = $this->user('2026-10-25', '101');
        $second = $this->user('2026-10-25', '102');
        $this->stat($this->bot($first), '2026-11-02', ['work_api_requests_count' => 1]);
        $unknown = $this->user('2026-11-02', null);
        $key = AdvertisingSource::fromAcquisition($first->acquisition)->key();

        Livewire::test(Advertising::class)->assertSee($first->email)->assertSee($second->email)->assertDontSee($unknown->email)
            ->call('selectCampaign', $key)->assertSee($first->email)->assertDontSee($second->email)
            ->set('stage', 'used')->assertSee($first->email)
            ->set('search', 'absent@example.test')->assertDontSee($first->email)
            ->set('search', '')->set('channel', 'unknown')->set('stage', 'all')
            ->assertSet('campaign', '')->assertSee($unknown->email)->assertDontSee($first->email)
            ->set('channel', 'all')->set('period', 'invalid')->assertSee($first->email);

        for ($i = 0; $i < 26; $i++) {
            $this->user('2026-11-02', '103');
        }
        Livewire::test(Advertising::class)->call('gotoPage', 2)->assertSee($first->email)
            ->set('search', $second->email)->assertSee($second->email)->assertDontSee($first->email);
    }

    public function test_utm_values_are_escaped_and_dashboard_links_to_the_report(): void
    {
        $this->actingAs(User::factory()->create(['is_admin' => true]));
        $this->user('2026-10-25', '<script>alert(1)</script>');
        $this->get('/admin/advertising')->assertOk()->assertSee('&lt;script&gt;alert(1)&lt;/script&gt;', false)
            ->assertDontSee('<script>alert(1)</script>', false);
        $this->get('/admin')->assertOk()->assertSee('AdvertisingOverview');
        Livewire::test(AdvertisingOverview::class)->assertSee('Реклама · регистрации за 30 дней');
    }

    private function user(string $date, ?string $campaign): User
    {
        $user = User::factory()->create(['created_at' => $date.' 09:00:00']);
        if ($campaign !== null) {
            $user->acquisition()->create(['last_tagged_touch' => ['parameters' => ['utm_source' => 'yandex', 'utm_medium' => 'cpc', 'utm_campaign' => $campaign]]]);
        }

        return $user;
    }

    private function bot(User $user): Bot
    {
        $id = (string) Str::uuid();

        return Bot::query()->create([
            'user_id' => $user->id, 'public_id' => $id, 'name' => 'Report test bot',
            'telegram_token_encrypted' => '123:local-placeholder', 'telegram_token_hash' => hash('sha256', $id),
            'status' => BotStatus::Active,
        ]);
    }

    /** @param array<string, int|null> $counters */
    private function stat(Bot $bot, string $date, array $counters): void
    {
        BotDailyStat::query()->create(['bot_id' => $bot->id, 'date' => $date, ...$counters]);
    }
}
