<?php

declare(strict_types=1);

namespace Tests\Feature\Api;

use App\Enums\BotStatus;
use App\Models\Bot;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Symfony\Component\Process\Process;
use Tests\TestCase;

final class WebhookConcurrencyTest extends TestCase
{
    use RefreshDatabase;

    public function test_simultaneous_receivers_commit_only_one_event_and_job(): void
    {
        Bot::create(['user_id' => User::factory()->create()->id, 'public_id' => 'bot_concurrent', 'name' => 'Concurrent',
            'telegram_token_encrypted' => 'test-token', 'telegram_token_hash' => hash('sha256', 'test-token'),
            'webhook_secret' => 'whsec_test', 'webhook_enabled' => true, 'webhook_url' => 'https://client.example.test/hook', 'status' => BotStatus::Active]);
        $file = storage_path('framework/testing/webhook-'.bin2hex(random_bytes(8)).'.sqlite');
        if (! is_dir(dirname($file))) {
            mkdir(dirname($file), 0777, true);
        }
        $barrier = $file.'.start';
        // VACUUM needs a committed fixture. RefreshDatabase detects this and remigrates
        // the next test instead of reusing this in-memory database's fixture data.
        DB::commit();
        DB::statement('VACUUM INTO ?', [$file]);
        $processes = [];
        try {
            foreach ([1, 2] as $index) {
                $process = new Process([PHP_BINARY, base_path('tests/Fixtures/receive-webhook.php'), $file, $barrier, (string) $index], base_path(), ['APP_ENV' => 'testing']);
                $process->start();
                $processes[] = $process;
            }
            $until = microtime(true) + 10;
            while ((! file_exists($barrier.'.ready-1') || ! file_exists($barrier.'.ready-2')) && microtime(true) < $until) {
                usleep(10000);
            }
            $this->assertFileExists($barrier.'.ready-1');
            $this->assertFileExists($barrier.'.ready-2');
            file_put_contents($barrier, 'start');
            foreach ($processes as $process) {
                $this->assertSame(0, $process->wait(), $process->getErrorOutput());
                $this->assertContains($process->getOutput(), ['accepted', 'retry']);
            }
            // Retry after any SQLite lock failure, as Telegram would. Already accepted is a no-op.
            $retry = new Process([PHP_BINARY, base_path('tests/Fixtures/receive-webhook.php'), $file, $barrier, 'retry'], base_path(), ['APP_ENV' => 'testing']);
            $retry->mustRun();
            $this->assertSame('accepted', $retry->getOutput());
            $db = new \PDO('sqlite:'.$file);
            $this->assertSame(1, (int) $db->query('SELECT COUNT(*) FROM webhook_events')->fetchColumn());
            $this->assertSame(1, (int) $db->query('SELECT COUNT(*) FROM jobs')->fetchColumn());
            $db = null;
        } finally {
            foreach ($processes as $process) {
                $process->stop();
            }
            foreach ([$file, $file.'-wal', $file.'-shm', $barrier, $barrier.'.ready-1', $barrier.'.ready-2', $barrier.'.ready-retry'] as $temporary) {
                if (is_file($temporary)) {
                    unlink($temporary);
                }
            }
        }
    }
}
