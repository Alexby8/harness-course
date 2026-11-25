<?php

declare(strict_types=1);

namespace Tests\Feature\Filament;

use App\Enums\BotStatus;
use App\Filament\Resources\ApiRequestLogResource\Pages\ListApiRequestLogs;
use App\Models\ApiRequestLog;
use App\Models\Bot;
use App\Models\User;
use Filament\Actions\Testing\TestAction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

final class ApiRequestLogResourceTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_table_distinguishes_all_three_results_and_filters_connection_failures(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $bot = Bot::query()->create([
            'user_id' => $admin->id, 'public_id' => 'bot_admin_results', 'name' => 'Admin result bot',
            'telegram_token_encrypted' => '123456789:local-test-placeholder',
            'telegram_token_hash' => hash('sha256', 'admin-result-token'), 'status' => BotStatus::Active,
        ]);
        $records = collect([true, false, null])->map(fn (?bool $ok): ApiRequestLog => ApiRequestLog::query()->create([
            'user_id' => $admin->id, 'bot_id' => $bot->id, 'method' => 'sendMessage', 'telegram_ok' => $ok,
        ]));
        $this->actingAs($admin);

        Livewire::test(ListApiRequestLogs::class)
            ->assertCanSeeTableRecords($records)
            ->assertTableColumnStateSet('telegram_ok', 'Да', $records[0])
            ->assertTableColumnStateSet('telegram_ok', 'Нет', $records[1])
            ->assertTableColumnStateSet('telegram_ok', 'Нет ответа', $records[2])
            ->filterTable('telegram_ok', '0')->assertCanSeeTableRecords([$records[1], $records[2]])->assertCanNotSeeTableRecords([$records[0]])
            ->filterTable('telegram_ok', 'no_response')->assertCanSeeTableRecords([$records[2]])->assertCanNotSeeTableRecords([$records[0], $records[1]])
            ->filterTable('telegram_ok', '1')->assertCanSeeTableRecords([$records[0]])->assertCanNotSeeTableRecords([$records[1], $records[2]]);
    }

    public function test_error_modal_shows_selectable_escaped_text_with_the_display_limit(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $bot = Bot::query()->create([
            'user_id' => $admin->id, 'public_id' => 'bot_admin_errors', 'name' => 'Admin error bot',
            'telegram_token_encrypted' => '123456789:local-test-placeholder',
            'telegram_token_hash' => hash('sha256', 'admin-error-token'), 'status' => BotStatus::Active,
        ]);
        $error = "Ошибка соединения\n".str_repeat('я', 70).'ERROR_DETAIL_END';
        $longError = '</textarea><script>alert(1)</script>'.str_repeat('я', 1000).'HIDDEN_ERROR_TAIL';
        $records = collect([null, '', $error, $longError])->map(fn (?string $error): ApiRequestLog => ApiRequestLog::query()->create([
            'user_id' => $admin->id, 'bot_id' => $bot->id, 'method' => 'sendMessage', 'error' => $error,
        ]));
        $this->actingAs($admin);

        $component = Livewire::test(ListApiRequestLogs::class)
            ->assertCanSeeTableRecords($records)
            ->assertDontSee('ERROR_DETAIL_END')
            ->assertActionDisabled(TestAction::make('viewError')->table($records[0]))
            ->assertActionDisabled(TestAction::make('viewError')->table($records[1]))
            ->mountAction(TestAction::make('viewError')->table($records[2]))
            ->assertActionMounted(TestAction::make('viewError')->table($records[2]))
            ->call('forceRender')
            ->assertSee($error)
            ->assertSee('readonly', false)
            ->assertSee('Скопировать всё')
            ->assertDontSee('текст сокращён');

        $this->assertFalse($component->instance()->getMountedAction()->isModalClosedByClickingAway());
        $this->assertNull($component->instance()->getMountedAction()->getModalSubmitAction());

        $component->unmountAction()
            ->mountAction(TestAction::make('viewError')->table($records[3]))
            ->call('forceRender')
            ->assertSee(str_repeat('я', 900))
            ->assertSee('Показаны первые 1000 символов')
            ->assertDontSee('HIDDEN_ERROR_TAIL')
            ->assertDontSee('</textarea><script>alert(1)</script>', false);

        $this->assertDatabaseHas('api_request_logs', ['id' => $records[3]->id, 'error' => $longError]);
    }
}
