<?php

namespace App\Observers;

use App\Caches\CountryAwareJobPageCache;
use App\Caches\CountryAwareLatestJobsCache;
use App\Caches\CountryAwareMostViewedJobsCache;
use App\Caches\ImprovedRelatedJobListingCache;
use App\Caches\JobCategoryCache;
use App\Caches\JobFilterCache;
use App\Caches\JobListingCache;
use App\Caches\JobPageCache;
use App\Caches\JobsCountCache;
use App\Caches\LatestJobsCache;
use App\Caches\MostViewedJobsCache;
use App\Caches\RelatedJobListingCache;
use App\Jobs\SubmitUrlToGoogleIndexing;
use App\Models\JobListing;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Throwable;

class JobListingObserver
{
    public function creating(JobListing $jobListing): void
    {
        $uuid = (string) Str::uuid();
        $slugUuid = Str::lower(str_replace('-', '', $uuid));
        $slugBase = Str::limit(Str::slug((string) $jobListing->job_title), 200, '');

        if ($slugBase === '') {
            $slugBase = 'job';
        }

        $jobListing->uuid = $uuid;
        $jobListing->slug = $slugBase.'-'.$slugUuid;
    }

    public function updating(JobListing $jobListing): void {}

    public function created(JobListing $jobListing): void
    {
        $this->afterMutation($jobListing, 'created');
    }

    public function updated(JobListing $jobListing): void
    {
        $this->afterMutation($jobListing, 'updated');
    }

    public function deleted(JobListing $jobListing): void
    {
        $this->afterMutation($jobListing, 'deleted');
    }

    private function afterMutation(JobListing $jobListing, string $event): void
    {
        try {
            $this->clearCache();
            RelatedJobListingCache::invalidateForCategory($jobListing->job_category);
            ImprovedRelatedJobListingCache::invalidateForCategory($jobListing->job_category);
        } catch (Throwable $e) {
            Log::warning('Job cache invalidation failed after model mutation', [
                'event' => $event,
                'job_listing_id' => $jobListing->id,
                'job_category' => $jobListing->job_category,
                'error' => $e->getMessage(),
                'exception' => get_class($e),
            ]);
        }
    }

    protected function clearCache(): void
    {
        Cache::forget('jobCategories');
        Cache::forget('jobCategoriesJobsCount');
        Cache::forget('jobCategoriesAll');

        JobListingCache::invalidate();
        JobPageCache::invalidate();
        MostViewedJobsCache::invalidate();
        LatestJobsCache::invalidate();

        CountryAwareMostViewedJobsCache::invalidate();
        CountryAwareLatestJobsCache::invalidate();
        CountryAwareJobPageCache::invalidate();

        JobCategoryCache::invalidate();
        JobFilterCache::invalidate();

        if ($this->shouldInvalidateCountCaches()) {
            JobsCountCache::invalidateLastWeekAdded();
            JobsCountCache::invalidateTodayAdded();
            JobsCountCache::invalidateAvailableJobsCount();
            JobsCountCache::invalidateCategoriesCount();
        }
    }

    private function shouldInvalidateCountCaches(): bool
    {
        try {
            $counter = (int) Cache::increment('job_count_cache_invalidation_counter');

            if ($counter >= 100) {
                Cache::put('job_count_cache_invalidation_counter', 0);
            }

            return $counter > 0 && $counter % 10 === 0;
        } catch (Throwable $e) {
            Log::warning('Job cache invalidation counter unavailable', [
                'error' => $e->getMessage(),
                'exception' => get_class($e),
            ]);

            return true;
        }
    }

    protected function dispatchGoogleIndexingJob(JobListing $jobListing, string $type): void
    {
        if (! config('services.google_indexing.enabled', false)) {
            return;
        }

        if (empty($jobListing->slug)) {
            return;
        }

        try {
            $url = route('job.show', $jobListing->slug);
            SubmitUrlToGoogleIndexing::dispatch($url, $type);
        } catch (Throwable $e) {
            Log::warning('Failed to dispatch Google indexing job', [
                'job_listing_id' => $jobListing->id,
                'slug' => $jobListing->slug,
                'type' => $type,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
