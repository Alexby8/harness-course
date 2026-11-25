<?php

declare(strict_types=1);

namespace Tests\Feature\Console;

use App\Enums\BotStatus;
use App\Models\Bot;
use App\Models\ProxyNode;
use App\Models\User;
use App\Services\TelegramWebhookUrl;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

final class SyncWebhooksCommandTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        config(['app.url' => 'https://app.example.test', 'botgate.webhook.base_url' => 'https://hooks.example.test', 'botgate.webhook.allowed_base_urls' => []]);
        ProxyNode::factory()->create(['url' => 'https://proxy.example.test']);
        Http::preventStrayRequests();
    }

    public function test_dry_run_reads_info_without_mutations(): void
    {
        $bot = $this->bot();
        $before = $bot->fresh()->getRawOriginal();
        Http::fakeSequence()->push($this->info($this->oldUrl($bot)));
        $this->artisan('botgate:webhooks:sync')->expectsOutputToContain('planned=1')->assertSuccessful();
        Http::assertSentCount(1);
        $this->assertSame($before, $bot->fresh()->getRawOriginal());
    }

    public function test_apply_preserves_settings_confirms_and_is_safe_to_rerun(): void
    {
        $bot = $this->bot();
        $before = $bot->fresh()->getRawOriginal();
        $target = app(TelegramWebhookUrl::class)->forBot($bot);
        Http::fakeSequence()->push($this->info($this->oldUrl($bot), ['allowed_updates' => ['message'], 'max_connections' => 12, 'ip_address' => '192.0.2.1']))
            ->push(['ok' => true, 'result' => true])->push($this->info($target))->push($this->info($target));
        $this->artisan('botgate:webhooks:sync', ['--apply' => true])->expectsOutputToContain('changed=1')->assertSuccessful();
        $this->artisan('botgate:webhooks:sync', ['--apply' => true])->expectsOutputToContain('skipped=1')->assertSuccessful();
        Http::assertSent(fn ($r) => $r['method'] === 'setWebhook' && $r['params'] === ['url' => $target, 'drop_pending_updates' => false, 'allowed_updates' => ['message'], 'max_connections' => 12]);
        Http::assertSentCount(4);
        $this->assertSame($before, $bot->fresh()->getRawOriginal());
    }

    public function test_missing_foreign_and_custom_certificate_are_conflicts(): void
    {
        $a = $this->bot();
        $b = $this->bot();
        $c = $this->bot();
        Http::fakeSequence()->push($this->info(''))->push($this->info($this->oldUrl($b).'?foreign=1'))
            ->push($this->info($this->oldUrl($c), ['has_custom_certificate' => true]));
        $this->artisan('botgate:webhooks:sync', ['--apply' => true])->expectsOutputToContain('conflict=3')->assertExitCode(1);
        Http::assertSentCount(3);
    }

    public function test_only_eligible_bots_and_public_id_filter_are_read(): void
    {
        $selected = $this->bot();
        $this->bot();
        $this->bot(['status' => BotStatus::Disabled]);
        $this->bot(['webhook_enabled' => false]);
        $this->bot(['webhook_url' => null]);
        $this->bot()->delete();
        Http::fakeSequence()->push($this->info($this->oldUrl($selected)));
        $this->artisan('botgate:webhooks:sync', ['--bot' => $selected->public_id])->assertSuccessful();
        Http::assertSentCount(1);
    }

    public function test_source_filter_and_explicit_rollback(): void
    {
        $bot = $this->bot();
        $new = app(TelegramWebhookUrl::class)->forBot($bot);
        config(['botgate.webhook.base_url' => null]);
        Http::fakeSequence()->push($this->info($new))->push(['ok' => true, 'result' => true])->push($this->info($this->oldUrl($bot)));
        $this->artisan('botgate:webhooks:sync', ['--apply' => true, '--base-url' => 'https://app.example.test', '--from-base-url' => 'https://hooks.example.test'])
            ->expectsOutputToContain('changed=1')->assertSuccessful();
        Http::assertSentCount(3);
    }

    public function test_source_filter_does_not_move_another_managed_origin(): void
    {
        $bot = $this->bot();
        Http::fakeSequence()->push($this->info($this->oldUrl($bot)));
        $this->artisan('botgate:webhooks:sync', ['--apply' => true, '--from-base-url' => 'https://previous.example.test'])
            ->expectsOutputToContain('source filter mismatch')->assertSuccessful();
        Http::assertSentCount(1);
    }

    public function test_timeout_is_reconciled_without_second_set_and_no_secret_output(): void
    {
        $bot = $this->bot();
        $calls = 0;
        Http::fake(function () use (&$calls, $bot) {
            $calls++;
            if ($calls === 2) {
                throw new ConnectionException('Sensitive '.$bot->telegram_token_encrypted.' '.$this->oldUrl($bot));
            }

            return Http::response($this->info($calls === 1 ? $this->oldUrl($bot) : app(TelegramWebhookUrl::class)->forBot($bot)));
        });
        $this->assertSame(0, Artisan::call('botgate:webhooks:sync', ['--apply' => true]));
        $this->assertStringContainsString('changed=1', Artisan::output());
        $this->assertStringNotContainsString($bot->webhook_secret, Artisan::output());
        $this->assertStringNotContainsString($bot->telegram_token_encrypted, Artisan::output());
        $this->assertSame(3, $calls);
    }

    public function test_unconfirmed_result_is_unknown_and_other_bots_continue(): void
    {
        $a = $this->bot();
        $b = $this->bot();
        Http::fakeSequence()->push($this->info($this->oldUrl($a)))->push('invalid', 502)->push($this->info($this->oldUrl($a)))
            ->push($this->info(app(TelegramWebhookUrl::class)->forBot($b)));
        $this->artisan('botgate:webhooks:sync', ['--apply' => true])->expectsOutputToContain('skipped=1 conflict=0 failed=0 unknown=1')->assertExitCode(3);
        Http::assertSentCount(4);
    }

    public function test_retry_after_is_reported_without_retry_or_exposing_description(): void
    {
        $bot = $this->bot();
        Http::fakeSequence()->push($this->info($this->oldUrl($bot)))->push(['ok' => false, 'error_code' => 429, 'description' => $bot->webhook_secret, 'parameters' => ['retry_after' => 30]], 400);
        $this->assertSame(1, Artisan::call('botgate:webhooks:sync', ['--apply' => true]));
        $this->assertStringContainsString('retry_after=30s', Artisan::output());
        $this->assertStringNotContainsString($bot->webhook_secret, Artisan::output());
        Http::assertSentCount(2);
    }

    public function test_invalid_origin_and_ineligible_selection_fail_without_http(): void
    {
        $this->artisan('botgate:webhooks:sync', ['--base-url' => 'https://user:secret@example.test/path'])->assertExitCode(2);
        $this->artisan('botgate:webhooks:sync', ['--bot' => 'missing'])->assertExitCode(1);
        Http::assertNothingSent();
    }

    public function test_read_failure_does_not_change_webhook_and_continues(): void
    {
        $a = $this->bot();
        $b = $this->bot();
        Http::fakeSequence()->push(['ok' => false, 'error_code' => 429, 'parameters' => ['retry_after' => 5]], 400)
            ->push($this->info(app(TelegramWebhookUrl::class)->forBot($b)));
        $this->artisan('botgate:webhooks:sync', ['--apply' => true])->expectsOutputToContain('skipped=1 conflict=0 failed=1')->assertExitCode(1);
        Http::assertSentCount(2);
    }

    public function test_apply_success_without_readback_is_unknown(): void
    {
        $bot = $this->bot();
        Http::fakeSequence()->push($this->info($this->oldUrl($bot)))->push(['ok' => true, 'result' => true])->push('bad gateway', 502);
        $this->artisan('botgate:webhooks:sync', ['--apply' => true])->expectsOutputToContain('unknown=1')->assertExitCode(3);
        Http::assertSentCount(3);
    }

    public function test_unfiltered_query_excludes_all_ineligible_bots(): void
    {
        $bot = $this->bot();
        $this->bot(['status' => BotStatus::Disabled]);
        $this->bot(['webhook_enabled' => false]);
        $this->bot(['webhook_url' => '']);
        $this->bot(['webhook_url' => null]);
        $this->bot()->delete();
        Http::fakeSequence()->push($this->info($this->oldUrl($bot)));
        $this->artisan('botgate:webhooks:sync')->expectsOutputToContain('planned=1')->assertSuccessful();
        Http::assertSentCount(1);
    }

    private function bot(array $attributes = []): Bot
    {
        return Bot::create(array_merge(['user_id' => User::factory()->create()->id, 'public_id' => 'bot_'.bin2hex(random_bytes(6)),
            'name' => 'Sync', 'telegram_token_encrypted' => 'token-for-test', 'telegram_token_hash' => bin2hex(random_bytes(32)),
            'status' => BotStatus::Active, 'webhook_enabled' => true, 'webhook_url' => 'https://client.example.test/hook', 'webhook_secret' => 'whsec_'.bin2hex(random_bytes(16))], $attributes));
    }

    private function oldUrl(Bot $bot): string
    {
        return 'https://app.example.test'.route('telegram.webhook', [$bot->public_id, $bot->webhook_secret], false);
    }

    private function info(string $url, array $extra = []): array
    {
        return ['ok' => true, 'result' => array_merge(['url' => $url, 'has_custom_certificate' => false, 'pending_update_count' => 0], $extra)];
    }
}
