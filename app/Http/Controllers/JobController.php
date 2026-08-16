<?php

namespace App\Http\Controllers;

use App\Caches\CountryAwareJobPageCache;
use App\Caches\JobListingCache;
use App\Caches\JobPageCache;
use App\Caches\JobViewsCache;
use App\Caches\ImprovedRelatedJobListingCache;
use App\Models\Country;
use App\Services\SearchSuggestionService;
use App\Services\SeoMetaService;
use App\Traits\DetectsUserCountry;
use Illuminate\Contracts\View\Factory;
use Illuminate\Contracts\View\View;
use Illuminate\Foundation\Application;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class JobController extends Controller
{
    use DetectsUserCountry;

    public function index(Request $request, SeoMetaService $seoService, SearchSuggestionService $searchService)
    {
        try {
            $userCountry = $this->getUserCountry();

            if (app()->environment('local') && ! $userCountry) {
                $userCountry = 'BD';
            }

            $jobs = CountryAwareJobPageCache::get($request, $userCountry);
        } catch (\Throwable $e) {
            Log::warning('Country-aware cache failed, falling back to default', [
                'error' => $e->getMessage(),
                'memory_usage' => memory_get_usage(true),
                'memory_peak' => memory_get_peak_usage(true),
            ]);

            $jobs = JobPageCache::get($request);
            $userCountry = null;
        }

        if ($request->filled('search')) {
            try {
                $appliedFilters = array_filter([
                    'city' => $request->city,
                    'country' => $request->country,
                    'category' => $request->category,
                    'remote' => $request->remote,
                    'types' => $request->types,
                ]);

                $searchService->trackSearch([
                    'query' => $request->search,
                    'user_id' => Auth::id(),
                    'results_count' => $jobs->total(),
                    'filters' => $appliedFilters,
                ]);
            } catch (\Exception $e) {
                Log::warning('Failed to track search', [
                    'query' => $request->search,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        $currentPage = $jobs->currentPage();
        $meta = $seoService->generateJobsIndexMeta($request, $jobs->total());
        $countries = Country::query()
            ->orderBy('name')
            ->get(['name', 'code']);

        return view('v2.job.index', [
            'jobs' => $jobs,
            'currentPage' => $currentPage,
            'userCountry' => $userCountry,
            'countries' => $countries,
            'meta' => $meta,
        ]);
    }

    public function job(SeoMetaService $seoService, $slug): Factory|Application|View|\Illuminate\Contracts\Foundation\Application
    {
        $jobViews = JobViewsCache::get($slug, request()->ip());
        $job = JobListingCache::get($slug);
        $relatedJobs = ImprovedRelatedJobListingCache::get($job);
        $meta = $seoService->generateJobDetailMeta($job);

        return view('v2.job.details', [
            'job' => $job,
            'relatedJobs' => $relatedJobs,
            'meta' => $meta,
        ]);
    }
}
