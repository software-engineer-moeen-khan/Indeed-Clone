<?php

namespace App\Pipelines;

use App\Models\Country;
use Closure;

class JobFilter
{
    public function handle($jobs, Closure $next)
    {
        $jobs->when(request()->filled('search'), function ($query) {
            $keyword = trim((string) request()->get('search'));

            $query->where(function ($searchQuery) use ($keyword) {
                $searchQuery
                    ->where('job_title', 'like', "%{$keyword}%")
                    ->orWhere('employer_name', 'like', "%{$keyword}%")
                    ->orWhere('description', 'like', "%{$keyword}%")
                    ->orWhere('publisher', 'like', "%{$keyword}%");
            });
        });

        $jobs->when(request()->filled('city'), function ($query) {
            $city = trim((string) request()->get('city'));
            $query->where('city', 'like', "%{$city}%");
        });

        $jobs->when(request()->filled('country'), function ($query) {
            $countryInput = trim((string) request()->get('country'));
            $country = Country::query()
                ->where('code', strtoupper($countryInput))
                ->orWhere('name', $countryInput)
                ->first(['code']);

            $query->where('country', $country?->code ?? $countryInput);
        });

        $jobs->when(request()->filled('category'), function ($query) {
            $query->where('job_category', request()->get('category'));
        });

        $jobs->when(request()->filled('types'), function ($query) {
            $types = request()->get('types', []);

            if (is_string($types)) {
                $types = array_filter(explode(',', $types));
            }

            $query->whereIn('employment_type', (array) $types);
        });

        $jobs->when(request()->filled('source'), function ($query) {
            $query->where('publisher', request()->get('source'));
        });

        $jobs->when(request()->filled('exclude_source'), function ($query) {
            $query->where('publisher', '!=', request()->get('exclude_source'));
        });

        if (request()->boolean('remote')) {
            $jobs->where('is_remote', true);
        }

        return $next($jobs);
    }
}
