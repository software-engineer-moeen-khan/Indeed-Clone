<?php

namespace App\Caches;

use App\Models\JobListing;
use Illuminate\Support\Facades\Cache;

class JobListingCache
{
    private const VERSION_KEY = 'job_detail_cache_version';

    public static function get($slug)
    {
        return Cache::remember(self::key($slug), 60 * 24, function () use ($slug) {
            return JobListing::where('slug', $slug)
                ->with('applyOptions')
                ->firstOrFail();
        });
    }

    public static function invalidate()
    {
        if (Cache::has(self::VERSION_KEY)) {
            Cache::increment(self::VERSION_KEY);
        } else {
            Cache::put(self::VERSION_KEY, 2, 60 * 24 * 30);
        }

        return true;
    }

    public static function key($slug)
    {
        $version = (int) Cache::get(self::VERSION_KEY, 1);

        return 'job_v'.$version.'_'.$slug;
    }
}
