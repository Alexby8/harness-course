<?php

declare(strict_types=1);

namespace Tests\Feature\Filament;

use App\Enums\BotStatus;
use App\Enums\WebhookEventStatus;
use App\Filament\Resources\WebhookEventResource\Pages\ListWebhookEvents;
use App\Models\Bot;
use App\Models\User;
use App\Models\WebhookEvent;
use Filament\Actions\Testing\TestAction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

final class WebhookEventResourceTest extends TestCase
{
    use RefreshDatabase;

    public function test_error_and_url_modals_show_selectable_escaped_text_without_changing_stored_values(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $bot = Bot::query()->create([
            'user_id' => $admin->id, 'public_id' => 'bot_admin_errors', 'name' => 'Admin error bot',
            'telegram_token_encrypted' => '123456789:local-test-placeholder',
            'telegram_token_hash' => hash('sha256', 'admin-error-token'), 'status' => BotStatus::Active,
        ]);
        $readableError = "Ошибка доставки\n".str_repeat('я', 60).'ERROR_DETAIL_END';
        $longError = '</textarea><img src=x onerror=alert(1)>'.str_repeat('я', 1000).'HIDDEN_ERROR_TAIL';
        $url = 'https://example.test/hook?query='.str_repeat('x', 100).'&tail=URL_DETAIL_END';
        $records = collect([null, '', $readableError, $longError])->map(fn (?string $error): WebhookEvent => WebhookEvent::query()->create([
            'bot_id' => $bot->id, 'payload' => [], 'target_url' => $url,
            'status' => WebhookEventStatus::Failed, 'last_error' => $error,
        ]));
        $this->actingAs($admin);

        $component = Livewire::test(ListWebhookEvents::class)
            ->assertCanSeeTableRecords($records)
            ->assertDontSee('ERROR_DETAIL_END')
            ->assertDontSee('URL_DETAIL_END')
            ->assertActionDisabled(TestAction::make('viewError')->table($records[0]))
            ->assertActionDisabled(TestAction::make('viewError')->table($records[1]))
            ->mountAction(TestAction::make('viewError')->table($records[2]))
            ->assertActionMounted(TestAction::make('viewError')->table($records[2]))
            ->call('forceRender')
            ->assertSee($readableError)
            ->assertSee('readonly', false)
            ->assertSee('Скопировать всё')
            ->assertDontSee('текст сокращён');

        $component->unmountAction()
            ->mountAction(TestAction::make('viewError')->table($records[3]))
            ->call('forceRender')
            ->assertSee(str_repeat('я', 900))
            ->assertSee('Показаны первые 1000 символов')
            ->assertDontSee('HIDDEN_ERROR_TAIL')
            ->assertDontSee('</textarea><img src=x onerror=alert(1)>', false);

        $component->unmountAction()
            ->mountAction(TestAction::make('viewUrl')->table($records[2]))
            ->assertActionMounted(TestAction::make('viewUrl')->table($records[2]))
            ->call('forceRender')
            ->assertSee($url)
            ->assertDontSee('ERROR_DETAIL_END')
            ->assertDontSee('текст сокращён');

        $this->assertDatabaseHas('webhook_events', ['id' => $records[3]->id, 'last_error' => $longError, 'target_url' => $url]);
    }
}
