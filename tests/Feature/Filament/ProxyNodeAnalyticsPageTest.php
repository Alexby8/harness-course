<?php

declare(strict_types=1);

namespace Tests\Feature\Filament;

use App\Filament\Resources\ProxyNodeResource;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class ProxyNodeAnalyticsPageTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_open_proxy_node_analytics_page(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);

        $this->actingAs($admin)
            ->get(ProxyNodeResource::getUrl('analytics'))
            ->assertOk()
            ->assertSee('Аналитика Proxy Nodes')
            ->assertSee('Последние 24 часа')
            ->assertSee('Доступность Proxy Nodes')
            ->assertSee('Задержка Proxy Nodes');
    }

    public function test_proxy_node_analytics_widgets_are_not_shown_on_dashboard(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);

        $this->actingAs($admin)
            ->get('/admin')
            ->assertOk()
            ->assertDontSee('Доступность Proxy Nodes')
            ->assertDontSee('Задержка Proxy Nodes');
    }
}
