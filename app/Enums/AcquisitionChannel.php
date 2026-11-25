<?php

declare(strict_types=1);

namespace App\Enums;

enum AcquisitionChannel: string
{
    case YandexDirect = 'yandex_direct';
    case OtherAdvertising = 'other_advertising';
    case OtherSources = 'other_sources';
    case Unknown = 'unknown';

    public function label(): string
    {
        return match ($this) {
            self::YandexDirect => 'Яндекс.Директ',
            self::OtherAdvertising => 'Другая реклама',
            self::OtherSources => 'Другие известные источники',
            self::Unknown => 'Источник неизвестен',
        };
    }

    public function isAdvertising(): bool
    {
        return $this === self::YandexDirect || $this === self::OtherAdvertising;
    }
}
