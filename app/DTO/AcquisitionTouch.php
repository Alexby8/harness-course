<?php

declare(strict_types=1);

namespace App\DTO;

final readonly class AcquisitionTouch
{
    /** @param array<string, string> $parameters */
    public function __construct(
        public array $parameters,
        public string $landingPath,
        public ?string $referrerHost,
        public int $capturedAt,
    ) {}

    /** @return array{parameters: array<string, string>, landing_path: string, referrer_host: ?string, captured_at: int} */
    public function toArray(): array
    {
        return [
            'parameters' => $this->parameters,
            'landing_path' => $this->landingPath,
            'referrer_host' => $this->referrerHost,
            'captured_at' => $this->capturedAt,
        ];
    }
}
