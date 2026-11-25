<?php

declare(strict_types=1);

namespace App\Enums;

enum ProxyNodeStatus: string
{
    case Unknown = 'unknown';
    case Online = 'online';
    case Offline = 'offline';

    public function label(): string
    {
        return match ($this) {
            self::Unknown => 'Не проверен',
            self::Online => 'Онлайн',
            self::Offline => 'Недоступен',
        };
    }
}
