<?php

declare(strict_types=1);

namespace App\Support;

use InvalidArgumentException;

final class AdvertisingMoney
{
    public static function kopecks(string $rubles): int
    {
        if (preg_match('/\A([0-9]{1,10})(?:\.([0-9]{1,2}))?\z/D', $rubles, $parts) !== 1) {
            throw new InvalidArgumentException('Укажите неотрицательную сумму с точностью до копеек.');
        }

        return (int) $parts[1] * 100 + (int) str_pad($parts[2] ?? '', 2, '0');
    }

    public static function format(int $kopecks): string
    {
        return number_format($kopecks / 100, 2, ',', ' ').' ₽';
    }

    public static function perUser(int $kopecks, int $count): string
    {
        return $count > 0 ? self::format((int) round($kopecks / $count)) : '—';
    }
}
