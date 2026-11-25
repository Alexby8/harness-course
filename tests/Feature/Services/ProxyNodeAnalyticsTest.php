<?php

declare(strict_types=1);

namespace Tests\Feature\Services;

use App\Models\ProxyNode;
use App\Services\Proxy\ProxyNodeAnalytics;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Date;
use Tests\TestCase;

final class ProxyNodeAnalyticsTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        Date::setTestNow();
        parent::tearDown();
    }

    public function test_it_builds_daily_latency_and_availability_series_for_each_node(): void
    {
        Date::setTestNow('2026-08-31 12:00:00');
        $node = ProxyNode::factory()->create(['name' => 'Proxy 01']);
        $node->healthChecks()->createMany([
            ['checked_at' => now()->startOfDay()->addHour(), 'successful' => true, 'latency_ms' => 100],
            ['checked_at' => now()->startOfDay()->addHours(2), 'successful' => true, 'latency_ms' => 200],
            ['checked_at' => now()->startOfDay()->addHours(3), 'successful' => false, 'latency_ms' => 300, 'error_code' => 'HTTP_ERROR'],
        ]);

        $chart = app(ProxyNodeAnalytics::class)->chart('7d');

        $this->assertCount(7, $chart['labels']);
        $this->assertSame(200.0, $chart['latency']['Proxy 01'][6]);
        $this->assertSame(66.7, $chart['availability']['Proxy 01'][6]);
    }
}
