<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Filament\Resources\GuideResource\Pages\ListGuides;
use App\Models\Guide;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Exceptions;
use Illuminate\Support\Str;
use Livewire\Livewire;
use RuntimeException;
use Tests\TestCase;

final class GuideViewsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Use Laravel's atomic database lock on the isolated SQLite database.
        config(['cache.stores.redis' => config('cache.stores.database')]);
        $this->withCookie(config('session.cookie'), Str::random(40));
    }

    public function test_first_view_reloads_and_revisits_with_different_utm_in_the_same_session(): void
    {
        config(['app.timezone' => 'Europe/Moscow']);
        $this->travelTo(Carbon::parse('2026-11-02 21:30:00', 'UTC'));
        [$guide, $other] = Guide::query()->orderBy('id')->take(2)->get()->all();

        $this->get('/guides')->assertOk()->assertDontSee('data-guide-view', false);
        $this->get($guide->path().'?utm_source=one')
            ->assertOk()->assertSee('data-guide-view="'.$guide->path().'/view"', false);
        $this->assertDatabaseEmpty('guide_daily_views');

        $this->post($guide->path().'/view')->assertNoContent();
        $this->get($guide->path())->assertOk();
        $this->post($guide->path().'/view')->assertNoContent();
        $this->get($guide->path().'?utm_source=two')->assertOk();
        $this->post($guide->path().'/view?utm_source=two')->assertNoContent();
        $this->post($other->path().'/view')->assertNoContent();

        $this->assertDatabaseCount('guide_daily_views', 2);
        $this->assertDatabaseHas('guide_daily_views', ['guide_id' => $guide->id, 'view_date' => '2026-11-03', 'views' => 1]);
        $this->assertDatabaseHas('guide_daily_views', ['guide_id' => $other->id, 'view_date' => '2026-11-03', 'views' => 1]);

        $this->withCookie(config('session.cookie'), Str::random(40))
            ->post($guide->path().'/view')->assertNoContent();
        $this->assertDatabaseHas('guide_daily_views', ['guide_id' => $guide->id, 'view_date' => '2026-11-03', 'views' => 2]);
    }

    public function test_window_is_24_hours_and_a_duplicate_does_not_extend_it(): void
    {
        $start = Carbon::parse('2026-11-03 10:00:00', config('app.timezone'));
        $path = Guide::query()->firstOrFail()->path().'/view';
        $this->travelTo($start);
        $this->post($path)->assertNoContent();

        $this->travelTo($start->copy()->addHours(24)->subSecond());
        $this->post($path)->assertNoContent();
        $this->assertDatabaseCount('guide_daily_views', 1);

        $this->travelTo($start->copy()->addHours(24));
        $this->post($path)->assertNoContent();
        $this->assertDatabaseCount('guide_daily_views', 2);
    }

    public function test_a_second_request_while_the_first_is_writing_does_not_count_again(): void
    {
        $path = Guide::query()->firstOrFail()->path().'/view';
        $overlapped = false;
        DB::connection()->beforeExecuting(function (string $query) use ($path, &$overlapped): void {
            if (! $overlapped && str_starts_with($query, 'insert into "guide_daily_views"')) {
                $overlapped = true;
                $this->post($path)->assertNoContent();
                $this->assertDatabaseEmpty('guide_daily_views');
            }
        });

        $this->post($path)->assertNoContent();
        $this->assertTrue($overlapped);
        $this->assertDatabaseCount('guide_daily_views', 1);
        $this->assertDatabaseHas('guide_daily_views', ['views' => 1]);
    }

    public function test_database_failure_allows_a_later_retry_and_article_still_loads(): void
    {
        Exceptions::fake();
        $guide = Guide::query()->firstOrFail();
        $fail = true;
        DB::connection()->beforeExecuting(function (string $query) use (&$fail): void {
            if ($fail && str_starts_with($query, 'insert into "guide_daily_views"')) {
                $fail = false;
                throw new RuntimeException('Simulated write failure');
            }
        });

        $this->post($guide->path().'/view')->assertServerError();
        $this->assertDatabaseEmpty('guide_daily_views');
        $this->get($guide->path())->assertOk();
        $this->post($guide->path().'/view')->assertNoContent();
        $this->assertDatabaseHas('guide_daily_views', ['guide_id' => $guide->id, 'views' => 1]);
    }

    public function test_only_published_articles_are_counted_even_when_metrika_is_disabled(): void
    {
        [$draft, $published] = Guide::query()->orderBy('id')->take(2)->get()->all();
        $draft->update(['is_published' => false]);
        $this->post($draft->path().'/view')->assertNotFound();
        $this->post('/guides/no-such-guide/view')->assertNotFound();
        $this->assertDatabaseEmpty('guide_daily_views');

        $this->withCookie(config('cookie-consent.cookie'), json_encode([
            'id' => (string) Str::uuid(),
            'choice' => 'declined',
            'version' => config('cookie-consent.version'),
            'expiresAt' => now()->addDay()->getTimestampMs(),
        ], JSON_THROW_ON_ERROR))->post($published->path().'/view')->assertNoContent();
        $this->assertDatabaseHas('guide_daily_views', ['guide_id' => $published->id, 'views' => 1]);
    }

    public function test_post_requires_csrf_and_is_throttled(): void
    {
        $this->app['env'] = 'local'; // Enable Laravel's normal CSRF check.
        $path = Guide::query()->firstOrFail()->path();
        $this->post($path.'/view')->assertStatus(419);

        $this->get($path)->assertOk();
        $this->withHeader('X-CSRF-TOKEN', session()->token());
        for ($i = 0; $i < 60; $i++) {
            $this->post($path.'/view')->assertNoContent();
        }
        $this->post($path.'/view')->assertTooManyRequests();
        $this->assertDatabaseHas('guide_daily_views', ['views' => 1]);
    }

    public function test_admin_shows_zero_and_sorts_both_totals_using_calendar_days(): void
    {
        config(['app.timezone' => 'Europe/Moscow']);
        $this->travelTo(Carbon::parse('2026-11-02 21:30:00', 'UTC')); // Nov 3 in Moscow.
        [$first, $second, $empty] = Guide::query()->orderBy('sort_order')->take(3)->get()->all();
        DB::table('guide_daily_views')->insert([
            ['guide_id' => $first->id, 'view_date' => '2026-10-04', 'views' => 10],
            ['guide_id' => $first->id, 'view_date' => '2026-10-05', 'views' => 2],
            ['guide_id' => $first->id, 'view_date' => '2026-11-03', 'views' => 3],
            ['guide_id' => $second->id, 'view_date' => '2026-11-03', 'views' => 6],
        ]);
        $this->actingAs(User::factory()->create(['is_admin' => true]));

        Livewire::test(ListGuides::class)
            ->assertTableColumnStateSet('views_last_30_days', 0, $empty)
            ->assertTableColumnStateSet('views_total', 0, $empty)
            ->assertTableColumnStateSet('views_last_30_days', 5, $first)
            ->assertTableColumnStateSet('views_total', 15, $first)
            ->sortTable('views_last_30_days', 'desc')
            ->assertCanSeeTableRecords([$second, $first, $empty], inOrder: true)
            ->sortTable('views_total', 'desc')
            ->assertCanSeeTableRecords([$first, $second, $empty], inOrder: true);
    }
}
