<?php

declare(strict_types=1);

namespace App\Filament\Widgets;

use App\Filament\Widgets\Concerns\HasDayWeekFilter;
use App\Models\User;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Facades\Log;

class UserRegistrationsChart extends ChartWidget
{
    use HasDayWeekFilter;

    protected ?string $heading = 'Регистрации пользователей';

    protected static ?int $sort = 1;

    protected function getType(): string
    {
        return 'line';
    }

    protected function getData(): array
    {
        Log::debug('[UserRegistrationsChart] Building chart data', ['filter' => $this->filter]);

        $rows = User::query()
            ->selectRaw('DATE(created_at) as date, COUNT(*) as count')
            ->where('created_at', '>=', $this->periodStart())
            ->whereNotNull('email_verified_at')
            ->groupBy('date')
            ->get();

        Log::debug('[UserRegistrationsChart] Fetched aggregated rows', ['rows' => $rows->count()]);

        return [
            'datasets' => [
                [
                    'label' => 'Новые пользователи',
                    'data' => $this->bucketByPeriod($rows, 'count'),
                    'borderColor' => '#f59e0b',
                    'backgroundColor' => 'rgba(245, 158, 11, 0.1)',
                    'fill' => true,
                ],
            ],
            'labels' => $this->periodLabels(),
        ];
    }
}
