<?php

declare(strict_types=1);

namespace Tests\Feature\Services;

use App\Enums\ProxyNodeHealthErrorCode;
use App\Enums\ProxyNodeStatus;
use App\Models\ProxyNode;
use App\Services\Proxy\ProxyNodeHealthChecker;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

final class ProxyNodeHealthCheckerTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        Date::setTestNow();

        parent::tearDown();
    }

    public function test_successful_health_check_marks_node_online_and_records_metadata(): void
    {
        Date::setTestNow('2026-08-28 12:00:00');
        $node = ProxyNode::factory()->create([
            'node_id' => 'proxy-02',
            'url' => 'https://proxy-02.example.test',
            'status' => ProxyNodeStatus::Offline,
        ]);
        Http::fake([
            'https://proxy-02.example.test/health' => Http::response([
                'status' => 'ok',
                'node_id' => 'proxy-02',
                'version' => '1.2.3',
                'timestamp' => 1_787_920_000,
            ]),
        ]);

        $result = app(ProxyNodeHealthChecker::class)->check($node);
        $node->refresh();

        $this->assertTrue($result->successful);
        $this->assertSame(ProxyNodeStatus::Online, $node->status);
        $this->assertSame('1.2.3', $node->version);
        $this->assertTrue($node->last_check_at?->equalTo(Date::now()));
        $this->assertTrue($node->last_success_at?->equalTo(Date::now()));
        $this->assertNull($node->last_failure_at);
        $this->assertSame($result->responseTimeMs, $node->response_time_ms);
        $this->assertDatabaseHas('proxy_node_health_checks', [
            'proxy_node_id' => $node->id,
            'successful' => true,
            'version' => '1.2.3',
            'error_code' => null,
        ]);
    }

    public function test_mismatched_node_id_marks_node_offline_without_accepting_metadata(): void
    {
        $node = ProxyNode::factory()->create([
            'node_id' => 'proxy-02',
            'url' => 'https://proxy-02.example.test',
            'version' => 'known-version',
        ]);
        Http::fake([
            'https://proxy-02.example.test/health' => Http::response([
                'status' => 'ok',
                'node_id' => 'unexpected-node',
                'version' => 'spoofed-version',
            ]),
        ]);

        $result = app(ProxyNodeHealthChecker::class)->check($node);
        $node->refresh();

        $this->assertFalse($result->successful);
        $this->assertSame(ProxyNodeStatus::Offline, $node->status);
        $this->assertSame('known-version', $node->version);
        $this->assertNotNull($node->last_failure_at);
        $this->assertDatabaseHas('proxy_node_health_checks', [
            'proxy_node_id' => $node->id,
            'successful' => false,
            'error_code' => ProxyNodeHealthErrorCode::InvalidResponse->value,
        ]);
    }

    public function test_connection_failure_marks_node_offline(): void
    {
        $node = ProxyNode::factory()->create([
            'url' => 'https://proxy-offline.example.test',
            'status' => ProxyNodeStatus::Online,
        ]);
        Http::fake([
            'https://proxy-offline.example.test/health' => Http::failedConnection(),
        ]);

        $result = app(ProxyNodeHealthChecker::class)->check($node);

        $this->assertFalse($result->successful);
        $this->assertSame('Ошибка подключения', $result->error);
        $this->assertSame(ProxyNodeStatus::Offline, $node->fresh()?->status);
        $this->assertDatabaseHas('proxy_node_health_checks', [
            'proxy_node_id' => $node->id,
            'successful' => false,
            'error_code' => ProxyNodeHealthErrorCode::ConnectionError->value,
        ]);
    }
}
