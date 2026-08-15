<?php

namespace App\Caches;

use App\Models\JobListing;
use Illuminate\Support\Facades\Cache;

class RelatedJobListingCache
{
    public static function get($slug, $job)
    {
        return Cache::remember(self::key($slug, $job->job_category), 60 * 24, function () use ($job) {
            $totalCount = JobListing::query()
                ->where('job_category', $job->job_category)
                ->where('id', '!=', $job->id)
                ->count();

            if ($totalCount <= 3) {
                return JobListing::query()
                    ->where('job_category', $job->job_category)
                    ->where('id', '!=', $job->id)
                    ->select('id', 'employer_name', 'slug', 'state', 'country', 'employment_type', 'job_title', 'min_salary', 'max_salary', 'salary_period', 'created_at', 'description', 'employer_logo', 'posted_at')
                    ->get();
            }

            $randomSeed = crc32($job->slug . date('Y-m-d'));
            $randomOffset = $randomSeed % max(1, $totalCount - 2);

            return JobListing::query()
                ->where('job_category', $job->job_category)
                ->where('id', '!=', $job->id)
                ->select('id', 'employer_name', 'slug', 'state', 'country', 'employment_type', 'job_title', 'min_salary', 'max_salary', 'salary_period', 'created_at', 'description', 'employer_logo', 'posted_at')
                ->orderBy('id')
                ->offset($randomOffset)
                ->limit(3)
                ->get();
        });
    }

    public static function invalidate(): bool
    {
        $version = (int) Cache::get('related_jobs_global_version', 1);
        return Cache::forever('related_jobs_global_version', $version + 1);
    }

    public static function invalidateForCategory($jobCategory): bool
    {
        $versionKey = self::categoryVersionKey($jobCategory);
        $version = (int) Cache::get($versionKey, 1);

        return Cache::forever($versionKey, $version + 1);
    }

    public static function key($slug, $jobCategory = null): string
    {
        $globalVersion = (int) Cache::get('related_jobs_global_version', 1);
        $categoryVersion = $jobCategory !== null
            ? (int) Cache::get(self::categoryVersionKey($jobCategory), 1)
            : 1;

        return 'related_jobs_v' . $globalVersion . '_category_' . $jobCategory . '_v' . $categoryVersion . '_' . $slug;
    }

    private static function categoryVersionKey($jobCategory): string
    {
        return 'related_jobs_category_version_' . $jobCategory;
    }
}
