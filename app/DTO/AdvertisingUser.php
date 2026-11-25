<?php

declare(strict_types=1);

namespace App\DTO;

use Carbon\CarbonImmutable;

final readonly class AdvertisingUser
{
    public function __construct(
        public int $id,
        public string $email,
        public CarbonImmutable $registeredAt,
        public bool $verified,
        public int $bots,
        public AdvertisingSource $source,
        public ?bool $used,
        public ?bool $active,
        public bool $retentionMatured,
        public ?bool $retained,
    ) {}

    public function usageLabel(?bool $value): string
    {
        return match ($value) {
            true => 'Да',
            false => 'Нет',
            null => 'Неполные данные',
        };
    }

    public function retentionLabel(): string
    {
        return $this->retentionMatured ? $this->usageLabel($this->retained) : 'Срок не прошёл';
    }
}
