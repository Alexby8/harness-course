<?php

declare(strict_types=1);

namespace App\Enums;

enum ProxyNodeHealthErrorCode: string
{
    case HttpError = 'HTTP_ERROR';
    case InvalidResponse = 'INVALID_RESPONSE';
    case ConnectionError = 'CONNECTION_ERROR';
    case InternalError = 'INTERNAL_ERROR';

    public function label(): string
    {
        return match ($this) {
            self::HttpError => 'HTTP-ошибка',
            self::InvalidResponse => 'Некорректный ответ',
            self::ConnectionError => 'Ошибка подключения',
            self::InternalError => 'Внутренняя ошибка',
        };
    }
}
