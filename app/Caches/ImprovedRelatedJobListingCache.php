<?php

namespace App\Caches;

use App\Models\JobListing;
use Illuminate\Support\Facades\Cache;

class ImprovedRelatedJobListingCache
{
    public static function get(JobListing $job)
    {
        $version = (int) Cache::get(self::categoryVersionKey($job->job_category), 1);
        $cacheKey = 'related_jobs_v2_category_' . $job->job_category . '_v' . $version . '_' . $job->slug;

        return Cache::remember($cacheKey, 60 * 24, function () use ($job) {
            return self::getRelatedJobsRandomSampling($job);
        });
    }

    private static function getRelatedJobsRandomSampling(JobListing $job)
    {
        $seed = crc32($job->slug . date('Y-m-d')) % 1000000;

        return JobListing::query()
            ->where('job_category', (int) $job->job_category)
            ->where('id', '!=', $job->id)
            ->select('id', 'employer_name', 'slug', 'state', 'country', 'employment_type', 'job_title', 'min_salary', 'max_salary', 'salary_period', 'created_at', 'description', 'employer_logo', 'posted_at')
            ->orderByRaw('RAND(?)', [$seed])
            ->limit(3)
            ->get();
    }

    public static function invalidateForCategory($category): bool
    {
        $versionKey = self::categoryVersionKey($category);
        $version = (int) Cache::get($versionKey, 1);

        return Cache::forever($versionKey, $version + 1);
    }

    private static function categoryVersionKey($category): string
    {
        return 'related_jobs_v2_category_version_' . $category;
    }
}
