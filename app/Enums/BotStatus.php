<?php

declare(strict_types=1);

namespace App\Enums;

enum BotStatus: string
{
    case Active = 'active';
    case Disabled = 'disabled';

    public function label(): string
    {
        return match($this) {
            self::Active => 'Активен',
            self::Disabled => 'Отключён',
        };
    }

    public function isActive(): bool
    {
        return $this === self::Active;
    }
}
