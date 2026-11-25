<?php

declare(strict_types=1);

namespace Tests\Feature\Filament;

use App\Enums\BotStatus;
use App\Filament\Widgets\ActiveBotsChart;
use App\Filament\Widgets\ActiveUsersChart;
use App\Filament\Widgets\ActiveUsersDailyChart;
use App\Filament\Widgets\ActiveUsersMonthlyChart;
use App\Models\Bot;
use App\Models\BotDailyStat;
use App\Models\User;
use Carbon\CarbonImmutable;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Livewire\Livewire;
use ReflectionMethod;
use Tests\TestCase;

final class ActiveUsersChartsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->travelTo(CarbonImmutable::parse('2026-01-31 12:00:00', config('app.timezone')));
    }

    public function test_daily_chart_counts_unique_owners_with_successful_requests_or_incoming_events(): void
    {
        $owner = User::factory()->create();
        $first = $this->bot($owner);
        $second = $this->bot($owner);
        $receiver = $this->bot();
        $failedOnly = $this->bot();
        $deliveryOnly = $this->bot();
        $this->bot(); // An enabled bot alone is not usage.

        $this->stat($first, '2026-01-31', ['api_requests_count' => 4, 'api_errors_count' => 3]);
        $this->stat($second, '2026-01-31', ['webhook_received_count' => 5]);
        $this->stat($receiver, '2026-01-31', ['webhook_received_count' => 1, 'webhook_failed_count' => 1]);
        $this->stat($failedOnly, '2026-01-31', ['api_requests_count' => 10, 'api_errors_count' => 10]);
        $this->stat($deliveryOnly, '2026-01-31', ['webhook_sent_count' => 1]);
        $this->stat($first, '2026-01-30', ['api_requests_count' => 1]);

        $removed = $this->bot();
        $this->stat($removed, '2026-01-30', ['webhook_received_count' => 1]);
        $removed->update(['status' => BotStatus::Disabled]);
        $removed->delete();
        $first->update(['status' => BotStatus::Disabled]);

        $this->stat($receiver, '2026-01-02', ['api_requests_count' => 1]);
        $this->stat($receiver, '2026-01-01', ['api_requests_count' => 1]);
        $this->stat($receiver, '2026-02-01', ['api_requests_count' => 1]);

        $data = $this->chartData(new ActiveUsersDailyChart);
        $expected = array_fill(0, 30, 0);
        $expected[0] = 1;
        $expected[28] = 2;
        $expected[29] = 2;

        $this->assertSame($expected, $data['datasets'][0]['data']);
        $this->assertSame('02.01', $data['labels'][0]);
        $this->assertSame('31.01', $data['labels'][29]);
    }

    public function test_monthly_chart_deduplicates_across_days_and_bots_and_uses_calendar_months(): void
    {
        $owner = User::factory()->create();
        $first = $this->bot($owner);
        $second = $this->bot($owner);
        $other = $this->bot();

        $this->stat($first, '2026-01-01', ['api_requests_count' => 1]);
        $this->stat($first, '2026-01-31', ['api_requests_count' => 1]);
        $this->stat($second, '2026-01-20', ['webhook_received_count' => 1]);
        $this->stat($other, '2026-01-22', ['webhook_received_count' => 1]);
        $this->stat($first, '2025-12-31', ['api_requests_count' => 1]);
        $this->stat($other, '2025-02-01', ['api_requests_count' => 1]);
        $this->stat($other, '2025-01-31', ['api_requests_count' => 1]);
        $this->stat($other, '2026-02-01', ['api_requests_count' => 1]);

        $data = $this->chartData(new ActiveUsersMonthlyChart);

        $this->assertSame([1, 0, 0, 0, 0, 0, 0, 0, 0, 0, 1, 2], $data['datasets'][0]['data']);
        $this->assertSame('02.2025', $data['labels'][0]);
        $this->assertSame('12.2025', $data['labels'][10]);
        $this->assertSame('01.2026 (неполный)', $data['labels'][11]);
    }

    public function test_empty_periods_show_zeroes(): void
    {
        $daily = new ActiveUsersDailyChart;
        $monthly = new ActiveUsersMonthlyChart;

        $this->assertSame(array_fill(0, 30, 0), $this->chartData($daily)['datasets'][0]['data']);
        $this->assertSame(array_fill(0, 12, 0), $this->chartData($monthly)['datasets'][0]['data']);
        $this->assertStringContainsString('Сегодня: 0.', $daily->getDescription());
        $this->assertStringContainsString('В текущем месяце: 0.', $monthly->getDescription());
    }

    public function test_admin_widgets_are_discovered_render_and_refresh_current_counts(): void
    {
        $this->actingAs(User::factory()->create(['is_admin' => true]));
        $widgets = Filament::getPanel('admin')->getWidgets();
        $this->assertContains(ActiveUsersDailyChart::class, $widgets);
        $this->assertContains(ActiveUsersMonthlyChart::class, $widgets);
        $this->assertNotContains(ActiveUsersChart::class, $widgets);
        $this->get('/admin')->assertOk();

        $daily = Livewire::test(ActiveUsersDailyChart::class)
            ->assertSee('Активные пользователи по дням')
            ->assertSee('Сегодня: 0.');

        $this->stat($this->bot(), '2026-01-31', ['api_requests_count' => 1]);

        $daily->call('updateChartData')->assertSee('Сегодня: 1.')
            ->assertDispatched('updateChartData');

        Livewire::test(ActiveUsersMonthlyChart::class)
            ->assertSee('Активные пользователи по месяцам')
            ->assertSee('В текущем месяце: 1.')
            ->assertSee('текущий месяц ещё не завершён');

        Livewire::test(ActiveBotsChart::class)->assertSee('Включённые боты')->assertDontSee('Активные боты');
    }

    private function bot(?User $owner = null): Bot
    {
        $id = (string) Str::uuid();

        return Bot::query()->create([
            'user_id' => ($owner ?? User::factory()->create())->id,
            'public_id' => $id,
            'name' => 'Activity test bot',
            'telegram_token_encrypted' => '123456789:local-test-placeholder',
            'telegram_token_hash' => hash('sha256', $id),
            'status' => BotStatus::Active,
        ]);
    }

    /** @param array<string, int> $counters */
    private function stat(Bot $bot, string $date, array $counters): void
    {
        BotDailyStat::query()->create(['bot_id' => $bot->id, 'date' => $date, ...$counters]);
    }

    /** @return array<string, mixed> */
    private function chartData(ActiveUsersChart $chart): array
    {
        return (new ReflectionMethod($chart, 'getData'))->invoke($chart);
    }
}
