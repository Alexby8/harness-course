<?php

declare(strict_types=1);

namespace App\Enums;

enum WebhookEventStatus: string
{
    case Pending = 'pending';
    case Processing = 'processing';
    case Sent = 'sent';
    case Failed = 'failed';

    public function label(): string
    {
        return match($this) {
            self::Pending => 'Ожидает',
            self::Processing => 'Обрабатывается',
            self::Sent => 'Доставлен',
            self::Failed => 'Ошибка',
        };
    }
}
