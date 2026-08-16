<?php

namespace App\Filament\Widgets;

use App\Models\JobListing;
use Filament\Widgets\ChartWidget;

class TopCountriesChart extends ChartWidget
{
    protected static ?string $heading = 'Top Hiring Countries';

    protected static ?string $description = 'Countries with the highest number of active job listings.';

    protected static ?int $sort = 4;

    protected int|string|array $columnSpan = 1;

    protected static ?string $maxHeight = '320px';

    protected function getData(): array
    {
        $countries = JobListing::query()
            ->selectRaw('country, COUNT(*) as jobs_count')
            ->groupBy('country')
            ->orderByDesc('jobs_count')
            ->limit(8)
            ->get();

        return [
            'datasets' => [
                [
                    'label' => 'Jobs',
                    'data' => $countries->pluck('jobs_count')->map(fn ($count) => (int) $count)->all(),
                    'backgroundColor' => [
                        '#2563eb', '#3b82f6', '#60a5fa', '#93c5fd',
                        '#16a34a', '#22c55e', '#f59e0b', '#f97316',
                    ],
                    'borderWidth' => 0,
                ],
            ],
            'labels' => $countries
                ->map(fn ($row) => $row->country ?: 'Not specified')
                ->all(),
        ];
    }

    protected function getType(): string
    {
        return 'doughnut';
    }

    protected function getOptions(): array
    {
        return [
            'plugins' => [
                'legend' => [
                    'position' => 'bottom',
                ],
            ],
            'cutout' => '64%',
            'maintainAspectRatio' => false,
            'responsive' => true,
        ];
    }
}
