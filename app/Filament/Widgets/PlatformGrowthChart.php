<?php

namespace App\Filament\Widgets;

use App\Models\JobListing;
use App\Models\JobUser;
use App\Models\User;
use Filament\Widgets\ChartWidget;
use Flowframe\Trend\Trend;
use Flowframe\Trend\TrendValue;
use Illuminate\Support\Carbon;

class PlatformGrowthChart extends ChartWidget
{
    protected static ?string $heading = 'Platform Growth — Last 30 Days';

    protected static ?string $description = 'Jobs published, user registrations and applications submitted.';

    protected static ?int $sort = 2;

    protected static ?string $pollingInterval = '60s';

    protected int|string|array $columnSpan = 'full';

    protected static ?string $maxHeight = '330px';

    protected function getData(): array
    {
        $start = now()->subDays(29)->startOfDay();
        $end = now()->endOfDay();

        $jobs = Trend::model(JobListing::class)
            ->between(start: $start, end: $end)
            ->perDay()
            ->count();

        $users = Trend::model(User::class)
            ->between(start: $start, end: $end)
            ->perDay()
            ->count();

        $applications = Trend::model(JobUser::class)
            ->between(start: $start, end: $end)
            ->perDay()
            ->count();

        return [
            'datasets' => [
                [
                    'label' => 'Jobs',
                    'data' => $jobs->map(fn (TrendValue $value) => (int) $value->aggregate)->all(),
                    'borderColor' => '#2563eb',
                    'backgroundColor' => 'rgba(37, 99, 235, 0.12)',
                    'tension' => 0.35,
                    'fill' => true,
                ],
                [
                    'label' => 'Users',
                    'data' => $users->map(fn (TrendValue $value) => (int) $value->aggregate)->all(),
                    'borderColor' => '#16a34a',
                    'backgroundColor' => 'rgba(22, 163, 74, 0.08)',
                    'tension' => 0.35,
                ],
                [
                    'label' => 'Applications',
                    'data' => $applications->map(fn (TrendValue $value) => (int) $value->aggregate)->all(),
                    'borderColor' => '#f59e0b',
                    'backgroundColor' => 'rgba(245, 158, 11, 0.08)',
                    'tension' => 0.35,
                ],
            ],
            'labels' => $jobs
                ->map(fn (TrendValue $value) => Carbon::parse($value->date)->format('M d'))
                ->all(),
        ];
    }

    protected function getType(): string
    {
        return 'line';
    }

    protected function getOptions(): array
    {
        return [
            'interaction' => [
                'mode' => 'index',
                'intersect' => false,
            ],
            'plugins' => [
                'legend' => [
                    'position' => 'bottom',
                ],
            ],
            'scales' => [
                'y' => [
                    'beginAtZero' => true,
                    'ticks' => [
                        'precision' => 0,
                    ],
                ],
            ],
            'maintainAspectRatio' => false,
            'responsive' => true,
        ];
    }
}
