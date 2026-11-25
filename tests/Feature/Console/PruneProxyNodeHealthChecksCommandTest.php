<?php

declare(strict_types=1);

namespace Tests\Feature\Console;

use App\Models\ProxyNode;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Date;
use Tests\TestCase;

final class PruneProxyNodeHealthChecksCommandTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        Date::setTestNow();
        parent::tearDown();
    }

    public function test_it_prunes_only_checks_older_than_configured_retention(): void
    {
        Date::setTestNow('2026-08-31 12:00:00');
        config()->set('botgate.proxy.health_history_retention_days', 30);
        $node = ProxyNode::factory()->create();
        $old = $node->healthChecks()->create([
            'checked_at' => now()->subDays(31),
            'successful' => true,
            'latency_ms' => 10,
        ]);
        $recent = $node->healthChecks()->create([
            'checked_at' => now()->subDays(29),
            'successful' => false,
            'latency_ms' => 20,
            'error_code' => 'CONNECTION_ERROR',
        ]);

        $this->artisan('botgate:proxy-node:prune-health-checks')->assertSuccessful();

        $this->assertDatabaseMissing('proxy_node_health_checks', ['id' => $old->id]);
        $this->assertDatabaseHas('proxy_node_health_checks', ['id' => $recent->id]);
    }
}
