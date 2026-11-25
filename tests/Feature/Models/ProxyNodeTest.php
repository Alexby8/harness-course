<?php

declare(strict_types=1);

namespace Tests\Feature\Models;

use App\Enums\ProxyNodeStatus;
use App\Models\ProxyNode;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

final class ProxyNodeTest extends TestCase
{
    use RefreshDatabase;

    public function test_secret_is_encrypted_at_rest_and_hidden_from_serialization(): void
    {
        $secret = '0123456789abcdef0123456789abcdef';
        $node = ProxyNode::factory()->create([
            'secret_encrypted' => $secret,
        ]);

        $storedSecret = DB::table('proxy_nodes')
            ->where('id', $node->id)
            ->value('secret_encrypted');

        $this->assertIsString($storedSecret);
        $this->assertNotSame($secret, $storedSecret);
        $this->assertSame($secret, $node->fresh()->secret_encrypted);
        $this->assertArrayNotHasKey('secret_encrypted', $node->toArray());
    }

    public function test_routable_scope_only_selects_online_enabled_nodes_and_uses_priority(): void
    {
        ProxyNode::factory()->create(['enabled' => false, 'priority' => 1]);
        ProxyNode::factory()->create([
            'status' => ProxyNodeStatus::Offline,
            'priority' => 2,
        ]);
        ProxyNode::factory()->create([
            'status' => ProxyNodeStatus::Unknown,
            'priority' => 3,
        ]);
        $expected = ProxyNode::factory()->create([
            'status' => ProxyNodeStatus::Online,
            'priority' => 10,
        ]);
        ProxyNode::factory()->create(['status' => ProxyNodeStatus::Online, 'priority' => 20]);

        $this->assertTrue($expected->isRoutable());
        $this->assertTrue($expected->is(ProxyNode::query()->routable()->first()));
    }
}
