<?php

declare(strict_types=1);

namespace App\Filament\Widgets;

final class ActiveUsersDailyChart extends ActiveUsersChart
{
    protected ?string $heading = 'Активные пользователи по дням';

    protected static ?int $sort = 6;
}
