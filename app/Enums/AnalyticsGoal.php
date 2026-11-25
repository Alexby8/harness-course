<?php

declare(strict_types=1);

namespace App\Enums;

use Illuminate\Support\Str;

enum AnalyticsGoal: string
{
    case Register = 'register';
    case RegisterFinal = 'register_final';
    case BotCreate = 'bot_create';

    /** @return array{id: string, name: string} */
    public function event(): array
    {
        return ['id' => (string) Str::uuid(), 'name' => $this->value];
    }

    public function route(): string
    {
        return match ($this) {
            self::Register => 'verification.notice',
            self::RegisterFinal => 'dashboard',
            self::BotCreate => 'bots.show',
        };
    }

    public function analyticsPath(): string
    {
        return match ($this) {
            self::Register => '/email/verify',
            self::RegisterFinal => '/dashboard',
            self::BotCreate => '/bots/created',
        };
    }
}
