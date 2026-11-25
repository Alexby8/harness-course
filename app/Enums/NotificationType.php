<?php

declare(strict_types=1);

namespace App\Enums;

enum NotificationType: string
{
    case Info = 'info';
    case Success = 'success';
    case Warning = 'warning';
    case Error = 'error';

    public function label(): string
    {
        return match($this) {
            self::Info => 'Информация',
            self::Success => 'Успех',
            self::Warning => 'Предупреждение',
            self::Error => 'Ошибка',
        };
    }

    public function colorClasses(): string
    {
        return match($this) {
            self::Info => 'bg-blue-900/40 border-blue-700 text-blue-300',
            self::Success => 'bg-green-900/40 border-green-700 text-green-300',
            self::Warning => 'bg-yellow-900/40 border-yellow-700 text-yellow-300',
            self::Error => 'bg-red-900/40 border-red-700 text-red-300',
        };
    }
}
