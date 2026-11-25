<?php

declare(strict_types=1);

namespace Tests\Feature\Filament;

use App\DTO\AdvertisingSource;
use App\Enums\AcquisitionChannel;
use App\Enums\BotStatus;
use App\Filament\Resources\AdvertisingCampaignResource;
use App\Filament\Resources\AdvertisingCampaignResource\Pages\CreateAdvertisingCampaign;
use App\Filament\Resources\AdvertisingCampaignResource\Pages\EditAdvertisingCampaign;
use App\Filament\Resources\AdvertisingCampaignResource\RelationManagers\ExpensesRelationManager;
use App\Models\AdvertisingCampaign;
use App\Models\AdvertisingExpense;
use App\Models\Bot;
use App\Models\BotDailyStat;
use App\Models\User;
use App\Services\AdvertisingCampaignPerformance;
use App\Support\AdvertisingMoney;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Livewire\Livewire;
use Tests\TestCase;

final class AdvertisingCampaignsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->travelTo(CarbonImmutable::parse('2026-11-20 12:00:00'));
    }

    public function test_only_admins_can_read_campaigns_or_change_expenses(): void
    {
        $campaign = $this->campaign();
        $this->get(AdvertisingCampaignResource::getUrl())->assertRedirect('/admin/login');
        $user = User::factory()->create(['is_admin' => false]);
        $this->actingAs($user)->get(AdvertisingCampaignResource::getUrl())->assertForbidden();
        $this->get(AdvertisingCampaignResource::getUrl('create'))->assertForbidden();
        $this->get(AdvertisingCampaignResource::getUrl('edit', ['record' => $campaign]))->assertForbidden();
        Livewire::test(CreateAdvertisingCampaign::class)->assertForbidden();
        Livewire::test(ExpensesRelationManager::class, ['ownerRecord' => $campaign, 'pageClass' => EditAdvertisingCampaign::class])->assertForbidden();
        $this->assertFalse(Gate::forUser($user)->allows('create', AdvertisingExpense::class));

        $this->admin();
        $this->get(AdvertisingCampaignResource::getUrl())->assertOk()->assertSee('Рекламные кампании');
        $this->get(AdvertisingCampaignResource::getUrl('create'))->assertOk()->assertSee('UTM campaign');
        $this->get(AdvertisingCampaignResource::getUrl('edit', ['record' => $campaign]))->assertOk();
    }

    public function test_expense_component_rechecks_admin_access_after_role_revocation(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $this->actingAs($admin);
        $manager = Livewire::test(ExpensesRelationManager::class, ['ownerRecord' => $this->campaign(), 'pageClass' => EditAdvertisingCampaign::class]);
        $admin->update(['is_admin' => false]);
        $manager->call('$refresh')->assertForbidden();
    }

    public function test_admin_creates_edits_and_archives_a_campaign_without_losing_expenses(): void
    {
        $this->admin();
        Livewire::test(CreateAdvertisingCampaign::class)->fillForm([
            'name' => 'Поиск', 'channel' => 'yandex_direct', 'external_id' => '001234',
            'utm_source' => 'Yandex', 'utm_medium' => 'CPC', 'utm_campaign' => '1234',
            'started_on' => '2026-10-25', 'stopped_on' => null, 'includes_vat' => false, 'archived' => false,
        ])->call('create')->assertHasNoFormErrors();
        $campaign = AdvertisingCampaign::query()->sole();
        $this->assertSame('yandex', $campaign->utm_source);
        $this->assertSame('cpc', $campaign->utm_medium);
        $this->assertSame('001234', $campaign->external_id);
        $this->assertFalse($campaign->includes_vat);
        $expense = $this->expense($campaign, '2026-10-25', '2026-10-31', '1000.10');

        Livewire::test(EditAdvertisingCampaign::class, ['record' => $campaign->id])->fillForm([
            'name' => 'Поиск — завершена', 'stopped_on' => '2026-11-02', 'archived' => true,
        ])->call('save')->assertHasNoFormErrors();
        $this->assertTrue($campaign->refresh()->archived);
        $this->assertSame('В архиве', $campaign->statusLabel());
        $this->assertSame('1000.10', $expense->refresh()->amount);
    }

    public function test_duplicate_source_and_inverted_dates_are_rejected_in_the_form(): void
    {
        $campaign = $this->campaign();
        $this->admin();
        Livewire::test(CreateAdvertisingCampaign::class)->fillForm([
            'name' => 'Дубликат', 'channel' => 'yandex_direct', 'utm_source' => 'YANDEX', 'utm_medium' => 'cpc', 'utm_campaign' => '1234',
        ])->call('create')->assertHasFormErrors(['utm_campaign']);
        Livewire::test(EditAdvertisingCampaign::class, ['record' => $campaign->id])->fillForm([
            'started_on' => '2026-11-10', 'stopped_on' => '2026-11-01',
        ])->call('save')->assertHasFormErrors(['stopped_on']);
        $this->assertSame(1, AdvertisingCampaign::query()->count());
    }

    public function test_admin_enters_edits_soft_deletes_and_restores_expenses(): void
    {
        $this->admin();
        $campaign = $this->campaign();
        $manager = Livewire::test(ExpensesRelationManager::class, ['ownerRecord' => $campaign, 'pageClass' => EditAdvertisingCampaign::class]);
        $manager->callTableAction('create', data: [
            'period_start' => '2026-11-01', 'period_end' => '2026-11-07', 'amount' => '1000.10', 'impressions' => 1000, 'clicks' => 15,
        ])->assertHasNoTableActionErrors();
        $expense = AdvertisingExpense::query()->sole();
        $this->assertSame($campaign->id, $expense->advertising_campaign_id);
        $this->assertSame(100010, $expense->kopecks());
        $manager->callTableAction('edit', $expense, data: ['amount' => '999.99'])->assertHasNoTableActionErrors();
        $this->assertSame('999.99', $expense->refresh()->amount);
        $manager->callTableAction('delete', $expense)->assertHasNoTableActionErrors();
        $this->assertSoftDeleted($expense);
        $this->assertSame(0, $campaign->expenses()->count());
        $manager->set('tableFilters.trashed.value', true)->callTableAction('restore', $expense)->assertHasNoTableActionErrors();
        $this->assertFalse($expense->refresh()->trashed());
    }

    public function test_expense_validation_rejects_overlaps_negative_fractional_counts_and_future_spending(): void
    {
        $this->admin();
        $campaign = $this->campaign();
        $this->expense($campaign, '2026-11-01', '2026-11-07', '1000.00');
        $manager = Livewire::test(ExpensesRelationManager::class, ['ownerRecord' => $campaign, 'pageClass' => EditAdvertisingCampaign::class]);
        $manager->callTableAction('create', data: ['period_start' => '2026-11-07', 'period_end' => '2026-11-09', 'amount' => '5.00'])
            ->assertHasTableActionErrors(['period_end']);
        $manager->unmountTableAction();
        $manager->callTableAction('create', data: ['period_start' => '2026-11-08', 'period_end' => '2026-11-09', 'amount' => '-1', 'clicks' => '1.5'])
            ->assertHasTableActionErrors(['amount', 'clicks']);
        $manager->unmountTableAction();
        $manager->callTableAction('create', data: ['period_start' => '2026-11-08', 'period_end' => '2026-11-09', 'amount' => '0.001'])
            ->assertHasTableActionErrors(['amount']);
        $manager->unmountTableAction();
        $manager->callTableAction('create', data: ['period_start' => '2026-11-21', 'period_end' => '2026-11-22', 'amount' => '1'])
            ->assertHasTableActionErrors(['period_start', 'period_end']);
        $manager->unmountTableAction();
        $manager->callTableAction('create', data: ['period_start' => '2026-11-08', 'period_end' => '2026-11-09', 'amount' => '0'])
            ->assertHasNoTableActionErrors();
        $this->assertSame(2, AdvertisingExpense::query()->count());
    }

    public function test_model_guards_ranges_for_create_edit_and_restore_but_allows_adjacent_periods(): void
    {
        $campaign = $this->campaign();
        $first = $this->expense($campaign, '2026-11-01', '2026-11-07', '10');
        $next = $this->expense($campaign, '2026-11-08', '2026-11-09', '20');
        try {
            $next->update(['period_start' => '2026-11-07']);
            $this->fail('Overlapping update was accepted.');
        } catch (ValidationException) {
            $this->assertSame('2026-11-08', $next->refresh()->period_start->toDateString());
        }
        $first->delete();
        $replacement = $this->expense($campaign, '2026-11-01', '2026-11-07', '10');
        try {
            $first->restore();
            $this->fail('Overlapping restore was accepted.');
        } catch (ValidationException) {
            $this->assertTrue($first->refresh()->trashed());
        }
        $replacement->delete();
        $this->assertTrue($first->restore());
        $this->assertSame(2, $campaign->expenses()->count());
    }

    public function test_cost_uses_click_date_and_later_registrations_and_deduplicates_users(): void
    {
        $campaign = $this->campaign();
        $expense = $this->expense($campaign, '2026-10-31', '2026-10-31', '1000.10');
        $first = $this->acquired('2026-10-31 23:59:59', '2026-11-01 09:00:00');
        $second = $this->acquired('2026-10-31 00:00:00', '2026-11-02 09:00:00');
        $second->forceFill(['email_verified_at' => null])->save();
        $this->work($first);
        $this->work($first); // Several bots still count as one person.
        $this->acquired('2026-11-01 00:00:00', '2026-11-01 09:00:00');
        $other = $this->acquired('2026-10-31 10:00:00', '2026-11-01 09:00:00');
        $other->acquisition()->update(['last_tagged_touch' => $this->touch('2026-10-31 10:00:00', 'other')]);
        $this->acquired('2026-10-31 10:00:00', '2026-11-01 09:00:00')->update(['is_admin' => true]);

        $result = app(AdvertisingCampaignPerformance::class)->forExpense($expense, $campaign);
        $this->assertSame(2, $result->totals['registered']);
        $this->assertSame(1, $result->totals['verified']);
        $this->assertSame(1, $result->totals['used']);
        $this->assertSame('500,05 ₽', $result->cost('registered'));
        $this->assertSame('1 000,10 ₽', $result->cost('used'));
    }

    public function test_missing_click_dates_unknown_usage_and_withdrawal_do_not_fabricate_results(): void
    {
        $campaign = $this->campaign();
        $expense = $this->expense($campaign, '2026-11-01', '2026-11-10', '1000.00');
        $missing = $this->acquired('2026-11-01', '2026-11-02');
        $touch = $this->touch('2026-11-01');
        unset($touch['captured_at']);
        $missing->acquisition()->update(['last_tagged_touch' => $touch]);
        $unknown = $this->acquired('2026-11-01', '2026-11-02');
        $this->work($unknown, null);
        $result = app(AdvertisingCampaignPerformance::class)->forExpense($expense, $campaign);
        $this->assertSame(1, $result->missingClickDate);
        $this->assertSame(1, $result->totals['registered']);
        $this->assertSame(1, $result->totals['usage_unknown']);
        $this->assertSame('—', $result->cost('used'));
        $unknown->acquisition()->delete();
        $result = app(AdvertisingCampaignPerformance::class)->forExpense($expense, $campaign);
        $this->assertSame(0, $result->totals['registered']);
        $this->assertSame('—', $result->cost('registered'));
    }

    public function test_money_is_exact_to_kopecks_and_optional_statistics_do_not_become_zeroes(): void
    {
        $expense = $this->expense($this->campaign(), '2026-11-01', '2026-11-01', '0.10');
        $this->assertSame(10, $expense->refresh()->kopecks());
        $this->assertNull($expense->impressions);
        $this->assertNull($expense->clicks);
        $this->assertSame('0,03 ₽', AdvertisingMoney::perUser(10, 3));
        $this->assertSame('0,00 ₽', AdvertisingMoney::perUser(0, 3));
        $this->assertSame('—', AdvertisingMoney::perUser(10, 0));
    }

    public function test_first_touch_fallback_and_old_expenses_are_not_limited_to_180_registration_days(): void
    {
        $campaign = $this->campaign();
        $expense = $this->expense($campaign, '2025-01-01', '2025-01-07', '700');
        $user = $this->acquired('2025-01-04', '2025-01-08');
        $user->acquisition()->update(['last_tagged_touch' => null, 'first_touch' => $this->touch('2025-01-04')]);
        $result = app(AdvertisingCampaignPerformance::class)->forExpense($expense, $campaign);
        $this->assertSame(1, $result->totals['registered']);
        $this->assertSame('700,00 ₽', $result->cost('registered'));
    }

    public function test_create_link_prefills_the_observed_source_without_creating_a_record(): void
    {
        $this->admin();
        $user = $this->acquired('2026-11-01', '2026-11-02');
        $key = AdvertisingSource::fromAcquisition($user->acquisition)->key();
        Livewire::withQueryParams(['source_key' => $key])->test(CreateAdvertisingCampaign::class)->assertFormSet([
            'channel' => 'yandex_direct', 'utm_source' => 'yandex', 'utm_medium' => 'cpc', 'utm_campaign' => '1234',
        ]);
        $this->assertSame(0, AdvertisingCampaign::query()->count());
    }

    private function admin(): void
    {
        $this->actingAs(User::factory()->create(['is_admin' => true]));
    }

    private function campaign(): AdvertisingCampaign
    {
        return AdvertisingCampaign::query()->create([
            'name' => 'Поиск', 'channel' => AcquisitionChannel::YandexDirect, 'utm_source' => 'yandex', 'utm_medium' => 'cpc', 'utm_campaign' => '1234',
        ]);
    }

    private function expense(AdvertisingCampaign $campaign, string $start, string $end, string $amount): AdvertisingExpense
    {
        return $campaign->expenses()->create(['period_start' => $start, 'period_end' => $end, 'amount' => $amount]);
    }

    private function acquired(string $click, string $registered): User
    {
        $user = User::factory()->create(['created_at' => $registered]);
        $user->acquisition()->create(['last_tagged_touch' => $this->touch($click)]);

        return $user;
    }

    /** @return array{parameters: array<string, string>, captured_at: int} */
    private function touch(string $date, string $source = 'yandex'): array
    {
        return ['parameters' => ['utm_source' => $source, 'utm_medium' => 'cpc', 'utm_campaign' => '1234'], 'captured_at' => CarbonImmutable::parse($date)->getTimestamp()];
    }

    private function work(User $user, ?int $count = 1): void
    {
        $id = (string) Str::uuid();
        $bot = Bot::query()->create([
            'user_id' => $user->id, 'public_id' => $id, 'name' => 'Cost test bot',
            'telegram_token_encrypted' => '123:placeholder', 'telegram_token_hash' => hash('sha256', $id), 'status' => BotStatus::Active,
        ]);
        BotDailyStat::query()->create(['bot_id' => $bot->id, 'date' => '2026-11-05', 'api_requests_count' => 1, 'work_api_requests_count' => $count]);
    }
}
