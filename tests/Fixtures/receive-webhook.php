<?php

declare(strict_types=1);
use App\Services\WebhookIngress;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Support\Facades\DB;

// Isolated test DB only; never reads the application's configured database.
require __DIR__.'/../../vendor/autoload.php';
$app = require __DIR__.'/../../bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();
config(['database.default' => 'sqlite', 'database.connections.sqlite.database' => $argv[1], 'database.connections.sqlite.url' => null]);
DB::purge('sqlite');
file_put_contents($argv[2].'.ready-'.$argv[3], 'ready');
$until = microtime(true) + 10;
while (! file_exists($argv[2]) && microtime(true) < $until) {
    usleep(10000);
}
try {
    $ok = app(WebhookIngress::class)->accept('bot_concurrent', 'whsec_test', ['update_id' => 42]);
    echo $ok ? 'accepted' : 'rejected';
} catch (Throwable) {
    // SQLite may reject a concurrent write; Telegram then retries the whole transaction.
    echo 'retry';
}
