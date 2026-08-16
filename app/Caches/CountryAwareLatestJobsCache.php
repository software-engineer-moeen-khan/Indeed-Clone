<?php

namespace App\Caches;

use App\Models\JobListing;
use Illuminate\Support\Facades\Cache;

class CountryAwareLatestJobsCache
{
    private const VERSION_KEY = 'country_aware_latest_jobs_cache_version';

    public static function get(array $excludeIds = [], ?string $userCountry = null, int $limit = 4)
    {
        return Cache::remember(self::key($userCountry, $excludeIds, $limit), 60 * 24, function () use ($excludeIds, $userCountry, $limit) {
            if ($userCountry) {
                $countryJobs = JobListing::query()
                    ->with('category')
                    ->whereNotIn('id', $excludeIds)
                    ->where('country', $userCountry)
                    ->latest()
                    ->limit($limit)
                    ->get();

                if ($countryJobs->count() < $limit) {
                    $needed = $limit - $countryJobs->count();
                    $countryExcludeIds = array_merge($excludeIds, $countryJobs->pluck('id')->toArray());

                    $globalJobs = JobListing::query()
                        ->with('category')
                        ->whereNotIn('id', $countryExcludeIds)
                        ->latest()
                        ->limit($needed)
                        ->get();

                    return $countryJobs->merge($globalJobs);
                }

                return $countryJobs;
            }

            return JobListing::query()
                ->with('category')
                ->whereNotIn('id', $excludeIds)
                ->latest()
                ->limit($limit)
                ->get();
        });
    }

    /**
     * Rotate the cache namespace so all country/exclusion variants become stale.
     * Cache::forget() does not support wildcard keys on Laravel's file cache.
     */
    public static function invalidate(?string $userCountry = null): void
    {
        Cache::forever(self::VERSION_KEY, (string) microtime(true));
    }

    public static function key(?string $userCountry = null, array $excludeIds = [], int $limit = 4): string
    {
        $excludeHash = md5(serialize($excludeIds));
        $version = (string) Cache::rememberForever(self::VERSION_KEY, fn () => '1');
        $scope = $userCountry ? "country_{$userCountry}" : 'global';

        return "latestJobs_{$scope}_limit_{$limit}_exclude_{$excludeHash}_v_{$version}";
    }
}
