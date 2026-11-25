<?php

declare(strict_types=1);

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void {}

    public function boot(): void
    {
        $this->configureRateLimiting();
    }

    private function configureRateLimiting(): void
    {
        RateLimiter::for('api_key', function (Request $request): Limit {
            $bearerToken = $request->bearerToken();
            $key = $bearerToken !== null
                ? 'api_key_'.hash('sha256', $bearerToken)
                : 'ip_'.$request->ip();

            return Limit::perMinute(
                config('botgate.rate_limits.api_key_per_minute', 600)
            )->by($key)->response(
                fn (Request $request, array $headers) => response()->json(
                    ['ok' => false, 'error' => 'Превышен лимит запросов'],
                    429,
                    $headers,
                )
            );
        });

        RateLimiter::for('bot', function (Request $request): Limit {
            $botPublicId = $request->route('botPublicId', '');

            return Limit::perMinute(
                config('botgate.rate_limits.bot_per_minute', 300)
            )->by('bot_'.$botPublicId)->response(
                fn (Request $request, array $headers) => response()->json(
                    ['ok' => false, 'error' => 'Превышен лимит запросов для этого бота'],
                    429,
                    $headers,
                )
            );
        });
    }
}
