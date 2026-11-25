<?php

declare(strict_types=1);

namespace App\Filament\Widgets;

final class ActiveUsersMonthlyChart extends ActiveUsersChart
{
    protected ?string $heading = 'Активные пользователи по месяцам';

    protected static ?int $sort = 7;

    protected bool $monthly = true;
}
