<?php

namespace App\Livewire;

use App\Caches\JobFilterCache;
use App\Models\Country;
use App\Models\JobCategory;
use App\Models\JobListing;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Log;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

class JobFilter extends Component
{
    use WithPagination;

    #[Url]
    public $search = '';

    #[Url]
    public $city = '';

    #[Url]
    public $country = '';

    #[Url]
    public $source = '';

    #[Url]
    public $exclude_source = '';

    #[Url]
    public $category = '';

    #[Url]
    public $remote = false;

    #[Url]
    public $types = [];

    public $perPage = 10;

    public $hasMorePages = false;

    protected $jobTypes = [
        'fulltime' => 'Full Time',
        'contractor' => 'Contractor',
        'parttime' => 'Part Time',
    ];

    public function mount(): void
    {
        if (! is_array($this->types)) {
            $this->types = array_filter(explode(',', (string) $this->types));
        }
    }

    public function hydrate(): void
    {
        if (! is_array($this->types)) {
            $this->types = array_filter(explode(',', (string) $this->types));
        }
    }

    public function updatedSearch(): void
    {
        $this->resetList();
    }

    public function updatedCity(): void
    {
        $this->resetList();
    }

    public function updatedCountry(): void
    {
        $this->resetList();
    }

    public function updatedSource(): void
    {
        $this->resetList();
    }

    public function updatedExcludeSource(): void
    {
        $this->resetList();
    }

    public function updatedCategory(): void
    {
        $this->resetList();
    }

    public function updatedRemote(): void
    {
        $this->resetList();
    }

    public function updatedTypes(): void
    {
        $this->resetList();
    }

    public function loadMore(): void
    {
        $this->perPage += 10;
    }

    public function toggleJobType(string $type): void
    {
        if (in_array($type, $this->types, true)) {
            $this->types = array_values(array_diff($this->types, [$type]));
        } else {
            $this->types[] = $type;
        }

        $this->resetList();
    }

    public function getActiveFilterCount(): int
    {
        return collect([
            $this->search,
            $this->city,
            $this->country,
            $this->source,
            $this->exclude_source,
            $this->category,
            $this->remote,
        ])->filter(fn ($value) => $value !== '' && $value !== null && $value !== false)->count()
            + count($this->types);
    }

    public function clearAllFilters(): void
    {
        $this->search = '';
        $this->city = '';
        $this->country = '';
        $this->source = '';
        $this->exclude_source = '';
        $this->category = '';
        $this->remote = false;
        $this->types = [];
        $this->perPage = 10;
        $this->resetPage();
    }

    protected function resetList(): void
    {
        $this->perPage = 10;
        $this->resetPage();
    }

    protected function getCategories()
    {
        try {
            return JobFilterCache::getCategories();
        } catch (\Throwable $e) {
            Log::warning('Failed to get categories from cache', ['error' => $e->getMessage()]);

            return JobCategory::query()->orderBy('name')->get();
        }
    }

    protected function getPublishers()
    {
        try {
            return JobFilterCache::getPublishers();
        } catch (\Throwable $e) {
            Log::warning('Failed to get publishers from cache', ['error' => $e->getMessage()]);

            return JobListing::query()
                ->whereNotNull('publisher')
                ->where('publisher', '!=', '')
                ->distinct()
                ->orderBy('publisher')
                ->pluck('publisher');
        }
    }

    protected function getCountries()
    {
        try {
            return JobFilterCache::getCountries();
        } catch (\Throwable $e) {
            Log::warning('Failed to get countries from cache', ['error' => $e->getMessage()]);

            return Country::query()
                ->where('is_active', true)
                ->orderBy('name')
                ->get()
                ->keyBy('code');
        }
    }

    protected function resolveCountryCode(string $value): string
    {
        $value = trim($value);

        if ($value === '') {
            return '';
        }

        $country = Country::query()
            ->where('code', strtoupper($value))
            ->orWhere('name', $value)
            ->first(['code']);

        return $country?->code ?? $value;
    }

    protected function buildQuery(): Builder
    {
        $query = JobListing::query()->with('category');

        $search = trim((string) $this->search);
        if ($search !== '') {
            $query->where(function (Builder $searchQuery) use ($search) {
                $searchQuery
                    ->where('job_title', 'like', "%{$search}%")
                    ->orWhere('employer_name', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%")
                    ->orWhere('publisher', 'like', "%{$search}%");
            });
        }

        $city = trim((string) $this->city);
        if ($city !== '') {
            $query->where('city', 'like', "%{$city}%");
        }

        $country = trim((string) $this->country);
        if ($country !== '') {
            $query->where('country', $this->resolveCountryCode($country));
        }

        if ($this->category !== '') {
            $query->where('job_category', $this->category);
        }

        if ($this->remote) {
            $query->where('is_remote', true);
        }

        if (! empty($this->types)) {
            $query->whereIn('employment_type', $this->types);
        }

        if ($this->source !== '') {
            $query->where('publisher', $this->source);
        }

        if ($this->exclude_source !== '') {
            $query->where('publisher', '!=', $this->exclude_source);
        }

        return $query;
    }

    public function render()
    {
        try {
            $query = $this->buildQuery();
            $total = (clone $query)->count();

            $jobs = $query
                ->orderByDesc('posted_at')
                ->orderByDesc('created_at')
                ->take($this->perPage)
                ->get();

            $this->hasMorePages = $total > $this->perPage;
            $this->dispatch('jobCountUpdated', $total);

            return view('livewire.job-filter', [
                'jobs' => $jobs,
                'categories' => $this->getCategories(),
                'publishers' => $this->getPublishers(),
                'countries' => $this->getCountries(),
                'jobTypes' => $this->jobTypes,
                'totalJobs' => $total,
            ]);
        } catch (\Throwable $e) {
            Log::error('JobFilter render error', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'user_authenticated' => auth()->check(),
            ]);

            return view('livewire.job-filter', [
                'jobs' => collect(),
                'categories' => collect(),
                'publishers' => collect(),
                'countries' => collect(),
                'jobTypes' => $this->jobTypes,
                'totalJobs' => 0,
            ]);
        }
    }
}
