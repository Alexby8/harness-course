<?php

declare(strict_types=1);

namespace App\Services\Guides;

use Illuminate\Contracts\Cache\LockProvider;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use LogicException;
use Throwable;

final class GuideViewCounter
{
    public function record(int $guideId, string $sessionId): void
    {
        $store = Cache::store('redis')->getStore();
        if (! $store instanceof LockProvider) {
            throw new LogicException('The guide view cache must support atomic locks.');
        }

        $key = hash_hmac('sha256', "guide-view:{$guideId}:{$sessionId}", config()->string('app.key'));
        $marker = $store->lock('guide-view:'.$key, 86400);

        if (! $marker->get()) {
            return;
        }

        try {
            DB::table('guide_daily_views')->upsert([
                'guide_id' => $guideId,
                'view_date' => now(config()->string('app.timezone'))->toDateString(),
                'views' => 1,
            ], ['guide_id', 'view_date'], ['views' => DB::raw('views + 1')]);
        } catch (Throwable $exception) {
            // Release only our own marker so a later request can retry.
            $marker->release();

            throw $exception;
        }
    }
}
