<?php

namespace App\Caches;

use App\Models\JobListing;
use Illuminate\Support\Facades\Cache;

class LatestJobsCache
{
    private const VERSION_KEY = 'latest_jobs_cache_version';

    public static function get(array $ids = [], int $limit = 6)
    {
        return Cache::remember(self::key($ids, $limit), 60 * 24, function () use ($ids, $limit) {
            return JobListing::query()
                ->with('category')
                ->when(! empty($ids), fn ($query) => $query->whereNotIn('id', $ids))
                ->latest()
                ->limit($limit)
                ->get();
        });
    }

    public static function invalidate(): void
    {
        Cache::forever(self::VERSION_KEY, (string) microtime(true));
    }

    public static function key(array $ids = [], int $limit = 6): string
    {
        $version = (string) Cache::rememberForever(self::VERSION_KEY, fn () => '1');
        $excludeHash = md5(serialize($ids));

        return "latestJobs_limit_{$limit}_exclude_{$excludeHash}_v_{$version}";
    }
}
