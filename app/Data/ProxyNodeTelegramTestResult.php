<?php

declare(strict_types=1);

namespace App\Data;

final readonly class ProxyNodeTelegramTestResult
{
    public function __construct(
        public bool $successful,
        public string $message,
    ) {}
}
