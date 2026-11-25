<?php

declare(strict_types=1);

namespace Tests\Feature\Console;

use App\Models\ProxyNode;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class AddProxyNodeCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_adds_an_enabled_node_without_exposing_the_secret(): void
    {
        $secret = '0123456789abcdef0123456789abcdef';

        $this->artisan('botgate:proxy-node:add', [
            'node_id' => 'proxy-02',
            'url' => 'https://proxy-02.bot-gate.ru',
            '--name' => 'Proxy #2',
            '--priority' => '10',
            '--enable' => true,
        ])
            ->expectsQuestion('NODE_SECRET (минимум 32 символа)', $secret)
            ->expectsOutputToContain('Proxy Node proxy-02 добавлен.')
            ->doesntExpectOutputToContain($secret)
            ->assertSuccessful();

        $node = ProxyNode::query()->sole();

        $this->assertSame('Proxy #2', $node->name);
        $this->assertSame($secret, $node->secret_encrypted);
        $this->assertSame(10, $node->priority);
        $this->assertTrue($node->enabled);
    }
}
