<?php

declare(strict_types=1);

namespace App\DTO;

use App\Support\AdvertisingMoney;

final readonly class AdvertisingExpenseResult
{
    /** @param array<string, int> $totals */
    public function __construct(public int $kopecks, public array $totals, public int $missingClickDate) {}

    public function cost(string $metric): string
    {
        return AdvertisingMoney::perUser($this->kopecks, $this->totals[$metric] ?? 0);
    }
}
