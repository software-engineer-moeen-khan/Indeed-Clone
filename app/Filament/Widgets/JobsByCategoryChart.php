<?php

namespace App\Filament\Widgets;

use App\Models\JobCategory;
use Filament\Widgets\ChartWidget;

class JobsByCategoryChart extends ChartWidget
{
    protected static ?string $heading = 'Top Job Categories';

    protected static ?string $description = 'Categories currently attracting the most job listings.';

    protected static ?int $sort = 3;

    protected int|string|array $columnSpan = 1;

    protected static ?string $maxHeight = '320px';

    protected function getData(): array
    {
        $categories = JobCategory::query()
            ->withCount('jobs')
            ->orderByDesc('jobs_count')
            ->limit(8)
            ->get();

        return [
            'datasets' => [
                [
                    'label' => 'Jobs',
                    'data' => $categories->pluck('jobs_count')->map(fn ($count) => (int) $count)->all(),
                    'backgroundColor' => '#2563eb',
                    'borderRadius' => 6,
                    'borderSkipped' => false,
                ],
            ],
            'labels' => $categories->pluck('name')->all(),
        ];
    }

    protected function getType(): string
    {
        return 'bar';
    }

    protected function getOptions(): array
    {
        return [
            'indexAxis' => 'y',
            'plugins' => [
                'legend' => [
                    'display' => false,
                ],
            ],
            'scales' => [
                'x' => [
                    'beginAtZero' => true,
                    'ticks' => [
                        'precision' => 0,
                    ],
                ],
                'y' => [
                    'grid' => [
                        'display' => false,
                    ],
                ],
            ],
            'maintainAspectRatio' => false,
            'responsive' => true,
        ];
    }
}
