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
use App\Support\JobDescriptionSanitizer;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Throwable;

class JobListingObserver
{
    public function saving(JobListing $jobListing): void
    {
        if (! $this->isNjpJob($jobListing)) {
            return;
        }

        $jobListing->description = JobDescriptionSanitizer::sanitize(
            (string) $jobListing->description,
            (string) $jobListing->job_title,
            (string) $jobListing->employer_name,
        );
    }

    public function creating(JobListing $jobListing): void
    {
        // time() can generate the same slug when multiple jobs with the same
        // title are imported within one second. Build the slug from the UUID so
        // every imported/admin-created job gets a collision-safe URL slug.
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

    /**
     * Cache refreshes are secondary work. A cache-store problem must never turn
     * a successful admin create/edit/delete into a 500 response.
     */
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

        // Google indexing remains disabled unless explicitly enabled in config.
        // $this->dispatchGoogleIndexingJob($jobListing, $event === 'deleted' ? 'URL_DELETED' : 'URL_UPDATED');
    }

    private function isNjpJob(JobListing $jobListing): bool
    {
        $publisher = Str::lower(trim((string) $jobListing->publisher));
        $applyLink = Str::lower(trim((string) $jobListing->apply_link));

        return str_contains($publisher, 'national jobs portal')
            || str_contains($publisher, 'national job portal')
            || str_contains($applyLink, 'njp.gov.pk');
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

            // Prefer refreshing count caches over failing an admin mutation.
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
