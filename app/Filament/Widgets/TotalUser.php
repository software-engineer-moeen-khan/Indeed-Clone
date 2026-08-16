<?php

namespace App\Filament\Widgets;

use App\Models\JobListing;
use App\Models\JobUser;
use App\Models\User;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Flowframe\Trend\Trend;
use Flowframe\Trend\TrendValue;

class TotalUser extends BaseWidget
{
    protected static ?int $sort = 1;

    protected static ?string $pollingInterval = '60s';

    protected function getColumns(): int
    {
        return 3;
    }

    protected function getStats(): array
    {
        $totalJobs = JobListing::count();
        $jobsThisWeek = JobListing::where('created_at', '>=', now()->subDays(7))->count();
        $jobsPreviousWeek = JobListing::whereBetween('created_at', [now()->subDays(14), now()->subDays(7)])->count();

        $totalUsers = User::count();
        $usersThisWeek = User::where('created_at', '>=', now()->subDays(7))->count();
        $usersPreviousWeek = User::whereBetween('created_at', [now()->subDays(14), now()->subDays(7)])->count();

        $totalApplications = JobUser::count();
        $applicationsThisWeek = JobUser::where('created_at', '>=', now()->subDays(7))->count();
        $applicationsPreviousWeek = JobUser::whereBetween('created_at', [now()->subDays(14), now()->subDays(7)])->count();

        $remoteJobs = JobListing::where('is_remote', true)->count();
        $remotePercentage = $totalJobs > 0 ? round(($remoteJobs / $totalJobs) * 100, 1) : 0;
        $totalViews = (int) JobListing::sum('views');

        $jobsGrowth = $this->growth($jobsThisWeek, $jobsPreviousWeek);
        $usersGrowth = $this->growth($usersThisWeek, $usersPreviousWeek);
        $applicationsGrowth = $this->growth($applicationsThisWeek, $applicationsPreviousWeek);

        return [
            Stat::make('Total Jobs', number_format($totalJobs))
                ->description($jobsThisWeek.' added in the last 7 days · '.$this->growthLabel($jobsGrowth))
                ->descriptionIcon($this->growthIcon($jobsGrowth))
                ->chart($this->trend(JobListing::class))
                ->color($jobsGrowth >= 0 ? 'success' : 'danger'),

            Stat::make('Registered Users', number_format($totalUsers))
                ->description($usersThisWeek.' new users · '.$this->growthLabel($usersGrowth))
                ->descriptionIcon($this->growthIcon($usersGrowth))
                ->chart($this->trend(User::class))
                ->color($usersGrowth >= 0 ? 'primary' : 'danger'),

            Stat::make('Applications', number_format($totalApplications))
                ->description($applicationsThisWeek.' submitted · '.$this->growthLabel($applicationsGrowth))
                ->descriptionIcon($this->growthIcon($applicationsGrowth))
                ->chart($this->trend(JobUser::class))
                ->color($applicationsGrowth >= 0 ? 'info' : 'danger'),

            Stat::make('Remote Jobs', number_format($remoteJobs))
                ->description($remotePercentage.'% of all job listings')
                ->descriptionIcon('heroicon-m-globe-alt')
                ->color('warning'),

            Stat::make('Total Job Views', number_format($totalViews))
                ->description('Combined visibility across all jobs')
                ->descriptionIcon('heroicon-m-eye')
                ->color('primary'),

            Stat::make('Application Rate', $totalJobs > 0 ? round($totalApplications / $totalJobs, 1) : 0)
                ->description('Average applications per job')
                ->descriptionIcon('heroicon-m-chart-bar-square')
                ->color('success'),
        ];
    }

    private function trend(string $model): array
    {
        return Trend::model($model)
            ->between(start: now()->subDays(6)->startOfDay(), end: now()->endOfDay())
            ->perDay()
            ->count()
            ->map(fn (TrendValue $value) => (int) $value->aggregate)
            ->values()
            ->all();
    }

    private function growth(int $current, int $previous): float
    {
        if ($previous === 0) {
            return $current > 0 ? 100 : 0;
        }

        return round((($current - $previous) / $previous) * 100, 1);
    }

    private function growthLabel(float $growth): string
    {
        return ($growth >= 0 ? '+' : '').$growth.'% vs previous week';
    }

    private function growthIcon(float $growth): string
    {
        return $growth >= 0 ? 'heroicon-m-arrow-trending-up' : 'heroicon-m-arrow-trending-down';
    }
}
