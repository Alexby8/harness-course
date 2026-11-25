<?php

declare(strict_types=1);

namespace App\Data;

final readonly class ProxyNodeHealthResult
{
    public function __construct(
        public bool $successful,
        public int $responseTimeMs,
        public ?string $error = null,
    ) {}
}
