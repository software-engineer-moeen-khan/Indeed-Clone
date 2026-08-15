<?php

namespace App\Caches;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class JobViewsCache
{
    public static function get($slug, $ip)
    {
        return Cache::remember(self::key($slug, $ip), 5 * 60, function () use ($slug) {
            MostViewedJobsCache::invalidate();

            // Update the counter directly so a page view does not trigger the
            // JobListing model observer and invalidate unrelated job caches.
            return DB::table('job_listings')
                ->where('slug', $slug)
                ->increment('views', 1);
        });
    }

    public static function invalidate($slug, $ip)
    {
        return Cache::forget(self::key($slug, $ip));
    }

    public static function key($slug, $ip): string
    {
        // Count a visitor at most once per five-minute cache lifetime.
        return 'job_' . $slug . '_view_' . sha1((string) $ip);
    }
}
