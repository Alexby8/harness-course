<?php

declare(strict_types=1);

namespace App\Services\Proxy;

use App\Models\ProxyNode;
use App\Models\ProxyNodeHealthCheck;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;

final class ProxyNodeAnalytics
{
    /**
     * @return array{labels: list<string>, latency: array<string, list<?float>>, availability: array<string, list<?float>>}
     */
    public function chart(string $period): array
    {
        [$start, $bucketHours, $labelFormat] = $this->periodDefinition($period);
        $end = CarbonImmutable::now()->startOfHour();
        $labels = [];
        $bucketKeys = [];

        for ($cursor = $start; $cursor <= $end; $cursor = $cursor->addHours($bucketHours)) {
            $key = $cursor->format('Y-m-d H:i:s');
            $bucketKeys[] = $key;
            $labels[] = $cursor->translatedFormat($labelFormat);
        }

        $checks = ProxyNodeHealthCheck::query()
            ->where('checked_at', '>=', $start)
            ->orderBy('checked_at')
            ->get()
            ->groupBy('proxy_node_id');

        $latency = [];
        $availability = [];

        foreach (ProxyNode::query()->orderBy('priority')->orderBy('id')->get() as $node) {
            $nodeChecks = $checks->get($node->getKey(), collect());
            $grouped = $nodeChecks->groupBy(
                fn (ProxyNodeHealthCheck $check): string => $this->bucketStart($check->checked_at, $bucketHours)->format('Y-m-d H:i:s'),
            );

            $latency[$node->name] = array_map(
                fn (string $key): ?float => $this->averageLatency($grouped->get($key)),
                $bucketKeys,
            );
            $availability[$node->name] = array_map(
                fn (string $key): ?float => $this->availabilityPercentage($grouped->get($key)),
                $bucketKeys,
            );
        }

        return compact('labels', 'latency', 'availability');
    }

    /** @return array{0: CarbonImmutable, 1: int, 2: string} */
    private function periodDefinition(string $period): array
    {
        return match ($period) {
            '24h' => [CarbonImmutable::now()->subHours(23)->startOfHour(), 1, 'H:i'],
            '30d' => [CarbonImmutable::now()->subDays(29)->startOfDay(), 24, 'd.m'],
            default => [CarbonImmutable::now()->subDays(6)->startOfDay(), 24, 'd.m'],
        };
    }

    private function bucketStart(CarbonImmutable $checkedAt, int $bucketHours): CarbonImmutable
    {
        if ($bucketHours === 24) {
            return $checkedAt->startOfDay();
        }

        return $checkedAt->startOfHour();
    }

    /** @param Collection<int, ProxyNodeHealthCheck>|null $checks */
    private function averageLatency(?Collection $checks): ?float
    {
        return $checks?->isNotEmpty() ? round((float) $checks->avg('latency_ms'), 1) : null;
    }

    /** @param Collection<int, ProxyNodeHealthCheck>|null $checks */
    private function availabilityPercentage(?Collection $checks): ?float
    {
        if ($checks === null || $checks->isEmpty()) {
            return null;
        }

        return round($checks->where('successful', true)->count() * 100 / $checks->count(), 1);
    }
}
