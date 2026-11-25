<?php

declare(strict_types=1);

namespace App\Filament\Widgets;

use App\Filament\Widgets\Concerns\HasDayWeekFilter;
use App\Models\User;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Facades\Log;

class TotalUsersChart extends ChartWidget
{
    use HasDayWeekFilter;

    protected ?string $heading = 'Всего пользователей';

    protected static ?int $sort = 0;

    protected function getType(): string
    {
        return 'line';
    }

    protected function getData(): array
    {
        Log::debug('[TotalUsersChart] Building chart data', ['filter' => $this->filter]);

        $periodStart = $this->periodStart();

        $baseline = User::query()
            ->whereNotNull('email_verified_at')
            ->where('created_at', '<', $periodStart)
            ->count();

        $rows = User::query()
            ->selectRaw('DATE(created_at) as date, COUNT(*) as count')
            ->whereNotNull('email_verified_at')
            ->where('created_at', '>=', $periodStart)
            ->groupBy('date')
            ->get();

        Log::debug('[TotalUsersChart] Fetched aggregated rows', ['rows' => $rows->count(), 'baseline' => $baseline]);

        $newPerBucket = $this->bucketByPeriod($rows, 'count');

        $running = $baseline;
        $totals = [];

        foreach ($newPerBucket as $count) {
            $running += $count;
            $totals[] = $running;
        }

        return [
            'datasets' => [
                [
                    'label' => 'Всего пользователей',
                    'data' => $totals,
                    'borderColor' => '#3b82f6',
                    'backgroundColor' => 'rgba(59, 130, 246, 0.1)',
                    'fill' => true,
                ],
            ],
            'labels' => $this->periodLabels(),
        ];
    }
}
