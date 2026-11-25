<?php

declare(strict_types=1);

namespace App\Services\Proxy;

use App\Data\ProxyNodeHealthResult;
use App\Enums\ProxyNodeHealthErrorCode;
use App\Enums\ProxyNodeStatus;
use App\Models\ProxyNode;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

final readonly class ProxyNodeHealthChecker
{
    public function __construct(private ProxyNodeClient $client) {}

    public function check(ProxyNode $node): ProxyNodeHealthResult
    {
        $startedAt = hrtime(true);

        try {
            $response = $this->client->health($node);
            $responseTimeMs = $this->durationMilliseconds($startedAt);
            $payload = $response->json();

            if (! $response->successful()) {
                return $this->failure(
                    $node,
                    $responseTimeMs,
                    ProxyNodeHealthErrorCode::HttpError,
                    'HTTP '.$response->status(),
                );
            }

            if (
                ! is_array($payload)
                || ($payload['status'] ?? null) !== 'ok'
                || ($payload['node_id'] ?? null) !== $node->node_id
                || ! is_string($payload['version'] ?? null)
                || $payload['version'] === ''
            ) {
                return $this->failure(
                    $node,
                    $responseTimeMs,
                    ProxyNodeHealthErrorCode::InvalidResponse,
                    'Некорректный ответ health endpoint',
                );
            }

            $checkedAt = now();
            $version = mb_substr($payload['version'], 0, 64);

            DB::transaction(function () use ($node, $checkedAt, $responseTimeMs, $version): void {
                $node->update([
                    'status' => ProxyNodeStatus::Online,
                    'last_check_at' => $checkedAt,
                    'last_success_at' => $checkedAt,
                    'response_time_ms' => $responseTimeMs,
                    'version' => $version,
                ]);

                $node->healthChecks()->create([
                    'checked_at' => $checkedAt,
                    'successful' => true,
                    'latency_ms' => $responseTimeMs,
                    'version' => $version,
                ]);
            });

            Log::info('Proxy node health check succeeded', [
                'proxy_node_id' => $node->getKey(),
                'node_id' => $node->node_id,
                'duration_ms' => $responseTimeMs,
                'version' => $node->version,
            ]);

            return new ProxyNodeHealthResult(true, $responseTimeMs);
        } catch (Throwable $exception) {
            $error = $exception instanceof ConnectionException
                ? 'Ошибка подключения'
                : 'Ошибка конфигурации или обработки ответа';
            $errorCode = $exception instanceof ConnectionException
                ? ProxyNodeHealthErrorCode::ConnectionError
                : ProxyNodeHealthErrorCode::InternalError;

            return $this->failure(
                $node,
                $this->durationMilliseconds($startedAt),
                $errorCode,
                $error,
                $exception,
            );
        }
    }

    private function failure(
        ProxyNode $node,
        int $responseTimeMs,
        ProxyNodeHealthErrorCode $errorCode,
        string $error,
        ?Throwable $exception = null,
    ): ProxyNodeHealthResult {
        $checkedAt = now();

        DB::transaction(function () use ($node, $checkedAt, $responseTimeMs, $errorCode): void {
            $node->update([
                'status' => ProxyNodeStatus::Offline,
                'last_check_at' => $checkedAt,
                'last_failure_at' => $checkedAt,
                'response_time_ms' => $responseTimeMs,
            ]);

            $node->healthChecks()->create([
                'checked_at' => $checkedAt,
                'successful' => false,
                'latency_ms' => $responseTimeMs,
                'version' => $node->version,
                'error_code' => $errorCode,
            ]);
        });

        Log::warning('Proxy node health check failed', [
            'proxy_node_id' => $node->getKey(),
            'node_id' => $node->node_id,
            'duration_ms' => $responseTimeMs,
            'reason' => $error,
            'error_code' => $errorCode->value,
            'exception_type' => $exception !== null ? $exception::class : null,
        ]);

        return new ProxyNodeHealthResult(false, $responseTimeMs, $error);
    }

    private function durationMilliseconds(int $startedAt): int
    {
        return max(0, (int) round((hrtime(true) - $startedAt) / 1_000_000));
    }
}
