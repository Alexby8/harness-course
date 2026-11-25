<?php

declare(strict_types=1);

namespace Tests\Feature\Services;

use App\Enums\ProxyNodeStatus;
use App\Exceptions\NoAvailableProxyNodeException;
use App\Models\ProxyNode;
use App\Services\Proxy\ProxyNodeHealthChecker;
use App\Services\Proxy\ProxyNodeManager;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

final class ProxyNodeManagerTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_routes_to_the_highest_priority_eligible_node(): void
    {
        ProxyNode::factory()->create([
            'url' => 'https://disabled.example.test',
            'enabled' => false,
            'priority' => 1,
        ]);
        ProxyNode::factory()->create([
            'url' => 'https://offline.example.test',
            'status' => ProxyNodeStatus::Offline,
            'priority' => 2,
        ]);
        ProxyNode::factory()->create([
            'url' => 'https://unknown.example.test',
            'status' => ProxyNodeStatus::Unknown,
            'priority' => 3,
        ]);
        ProxyNode::factory()->create([
            'url' => 'https://selected.example.test',
            'status' => ProxyNodeStatus::Online,
            'priority' => 10,
        ]);
        ProxyNode::factory()->create([
            'url' => 'https://lower-priority.example.test',
            'status' => ProxyNodeStatus::Online,
            'priority' => 20,
        ]);
        Http::fake(fn () => Http::response(['ok' => true]));

        app(ProxyNodeManager::class)->sendJson(
            '123456789:abcdefghijklmnopqrstuvwx',
            'getMe',
            [],
        );

        Http::assertSentCount(1);
        Http::assertSent(fn (Request $request): bool => $request->url() === 'https://selected.example.test/v1/telegram/json'
        );
    }

    public function test_it_fails_when_no_node_is_eligible(): void
    {
        ProxyNode::factory()->create(['enabled' => false]);
        ProxyNode::factory()->create(['status' => ProxyNodeStatus::Offline]);
        ProxyNode::factory()->create(['status' => ProxyNodeStatus::Unknown]);
        Http::fake();

        $this->expectException(NoAvailableProxyNodeException::class);

        app(ProxyNodeManager::class)->sendJson(
            '123456789:abcdefghijklmnopqrstuvwx',
            'getMe',
            [],
        );
    }

    public function test_it_does_not_retry_an_ambiguous_connection_failure_on_another_node(): void
    {
        ProxyNode::factory()->create([
            'url' => 'https://primary.example.test',
            'status' => ProxyNodeStatus::Online,
            'priority' => 10,
        ]);
        ProxyNode::factory()->create([
            'url' => 'https://secondary.example.test',
            'status' => ProxyNodeStatus::Online,
            'priority' => 20,
        ]);
        Http::fake([
            'https://primary.example.test/*' => Http::failedConnection('timeout'),
            'https://secondary.example.test/*' => Http::response(['ok' => true]),
        ]);

        try {
            app(ProxyNodeManager::class)->sendJson(
                '123456789:abcdefghijklmnopqrstuvwx',
                'sendMessage',
                ['chat_id' => 1, 'text' => 'Hello'],
            );
            $this->fail('An ambiguous connection failure was silently retried.');
        } catch (ConnectionException) {
            // Expected: a timeout cannot prove that Telegram did not process it.
        }

        Http::assertSentCount(1);
        Http::assertNotSent(fn (Request $request): bool => str_contains(
            $request->url(),
            'secondary.example.test',
        ));
    }

    public function test_health_check_failover_routes_around_a_known_offline_primary(): void
    {
        $primary = ProxyNode::factory()->create([
            'node_id' => 'proxy-primary',
            'url' => 'https://primary.example.test',
            'status' => ProxyNodeStatus::Online,
            'priority' => 10,
        ]);
        ProxyNode::factory()->create([
            'node_id' => 'proxy-secondary',
            'url' => 'https://secondary.example.test',
            'status' => ProxyNodeStatus::Online,
            'priority' => 20,
        ]);
        Http::fake([
            'https://primary.example.test/health' => Http::failedConnection('offline'),
            'https://secondary.example.test/v1/telegram/json' => Http::response(['ok' => true]),
        ]);

        app(ProxyNodeHealthChecker::class)->check($primary);
        app(ProxyNodeManager::class)->sendJson(
            '123456789:abcdefghijklmnopqrstuvwx',
            'getMe',
            [],
        );

        $this->assertSame(ProxyNodeStatus::Offline, $primary->fresh()?->status);
        Http::assertSent(fn (Request $request): bool => $request->url()
            === 'https://secondary.example.test/v1/telegram/json');
        Http::assertNotSent(fn (Request $request): bool => $request->url()
            === 'https://primary.example.test/v1/telegram/json');
    }
}
