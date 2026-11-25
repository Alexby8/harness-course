<?php

declare(strict_types=1);

namespace Tests\Feature\Console;

use App\Enums\ProxyNodeStatus;
use App\Models\ProxyNode;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

final class CheckProxyNodesCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_checks_all_enabled_nodes_and_skips_disabled_nodes(): void
    {
        $enabled = ProxyNode::factory()->create([
            'node_id' => 'proxy-enabled',
            'url' => 'https://enabled.example.test',
        ]);
        $disabled = ProxyNode::factory()->create([
            'node_id' => 'proxy-disabled',
            'url' => 'https://disabled.example.test',
            'enabled' => false,
            'status' => ProxyNodeStatus::Unknown,
        ]);
        Http::fake([
            'https://enabled.example.test/health' => Http::response([
                'status' => 'ok',
                'node_id' => 'proxy-enabled',
                'version' => 'test',
            ]),
        ]);

        $this->artisan('botgate:proxy-node:check')
            ->expectsOutputToContain('proxy-enabled')
            ->doesntExpectOutputToContain('proxy-disabled')
            ->assertSuccessful();

        $this->assertSame(ProxyNodeStatus::Online, $enabled->fresh()?->status);
        $this->assertSame(ProxyNodeStatus::Unknown, $disabled->fresh()?->status);
        Http::assertSentCount(1);
    }

    public function test_specific_disabled_node_can_be_checked_manually(): void
    {
        $node = ProxyNode::factory()->create([
            'node_id' => 'proxy-disabled',
            'url' => 'https://disabled.example.test',
            'enabled' => false,
        ]);
        Http::fake([
            'https://disabled.example.test/health' => Http::response([
                'status' => 'ok',
                'node_id' => 'proxy-disabled',
                'version' => 'test',
            ]),
        ]);

        $this->artisan('botgate:proxy-node:check', ['node_id' => $node->node_id])
            ->assertSuccessful();

        $this->assertSame(ProxyNodeStatus::Online, $node->fresh()?->status);
    }
}
