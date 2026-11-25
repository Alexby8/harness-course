<?php

declare(strict_types=1);

namespace App\Filament\Widgets;

use App\Filament\Widgets\Concerns\HasDayWeekFilter;
use App\Models\Bot;
use App\Models\User;
use Carbon\Carbon;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Facades\Log;

class UsersByBotCountChart extends ChartWidget
{
    use HasDayWeekFilter;

    protected ?string $heading = 'Пользователи по количеству ботов';

    protected static ?int $sort = 4;

    private const MAX_BAND = 4;

    protected function getType(): string
    {
        return 'line';
    }

    protected function getData(): array
    {
        Log::debug('[UsersByBotCountChart] Building chart data', ['filter' => $this->filter]);

        $users = User::query()->whereNotNull('email_verified_at')->pluck('created_at', 'id');

        $botsByUser = Bot::withTrashed()
            ->select('user_id', 'created_at', 'deleted_at')
            ->get()
            ->groupBy('user_id');

        $snapshots = $this->periodSnapshots();

        $bandCounts = [];

        foreach ($snapshots as $index => $snapshot) {
            $bands = array_fill(0, self::MAX_BAND + 1, 0);

            foreach ($users as $userId => $createdAt) {
                if (Carbon::parse($createdAt)->greaterThan($snapshot)) {
                    continue;
                }

                $botCount = 0;

                foreach ($botsByUser->get($userId, []) as $bot) {
                    $botCreatedAt = Carbon::parse($bot->created_at);
                    $botDeletedAt = $bot->deleted_at ? Carbon::parse($bot->deleted_at) : null;

                    if ($botCreatedAt->lessThanOrEqualTo($snapshot) && ($botDeletedAt === null || $botDeletedAt->greaterThan($snapshot))) {
                        $botCount++;
                    }
                }

                $band = min($botCount, self::MAX_BAND);
                $bands[$band]++;
            }

            $bandCounts[$index] = $bands;
        }

        Log::debug('[UsersByBotCountChart] Computed band counts', [
            'users' => $users->count(),
            'bots' => $botsByUser->flatten()->count(),
            'bands' => count($bandCounts),
        ]);

        $labels = [
            '0 ботов',
            '1 бот',
            '2 бота',
            '3 бота',
            '4+ ботов',
        ];

        $colors = [
            '#f59e0b',
            '#3b82f6',
            '#22c55e',
            '#ef4444',
            '#8b5cf6',
        ];

        $datasets = [];

        for ($band = 0; $band <= self::MAX_BAND; $band++) {
            $data = [];

            foreach ($bandCounts as $bands) {
                $data[] = $bands[$band];
            }

            if (array_sum($data) === 0) {
                continue;
            }

            [$r, $g, $b] = sscanf($colors[$band], '#%02x%02x%02x');

            $datasets[] = [
                'label' => $labels[$band],
                'data' => $data,
                'borderColor' => $colors[$band],
                'backgroundColor' => "rgba({$r}, {$g}, {$b}, 0.1)",
                'fill' => true,
            ];
        }

        Log::debug('[UsersByBotCountChart] Filtered zero-value bands', [
            'rendered_datasets' => count($datasets),
        ]);

        return [
            'datasets' => $datasets,
            'labels' => $this->periodLabels(),
        ];
    }

    /**
     * @return array<int, Carbon>
     */
    private function periodSnapshots(): array
    {
        $start = $this->periodStart();
        $steps = $this->periodSteps();
        $isWeekly = $this->filter === 'week';

        $snapshots = [];

        for ($i = 0; $i < $steps; $i++) {
            $snapshots[] = $isWeekly
                ? $start->copy()->addWeeks($i)->endOfWeek()
                : $start->copy()->addDays($i)->endOfDay();
        }

        return $snapshots;
    }
}
