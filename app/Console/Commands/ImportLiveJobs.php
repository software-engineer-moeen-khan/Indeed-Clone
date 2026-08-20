<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\JobCategory;
use App\Models\JobListing;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Throwable;

class ImportLiveJobs extends Command
{
    protected $signature = 'jobs:import-live
                            {--limit=10 : Number of jobs to sync per category}
                            {--category= : Import only one category name, slug, or query}
                            {--sources=himalayas,remotive,arbeitnow,jobicy : Comma-separated providers}';

    protected $description = 'Import real current jobs from public job APIs with real source/application links.';

    private const BIOMEDICAL_NAME = 'Biomedical Engineering';
    private const BIOMEDICAL_SLUG = 'biomedical-engineering';

    private array $feedCache = [];
    private int $requestCount = 0;

    public function handle(): int
    {
        $limit = max(1, min(50, (int) $this->option('limit')));
        $sources = $this->parseSources((string) $this->option('sources'));

        if ($sources === []) {
            $this->error('No supported source selected. Supported: himalayas, remotive, arbeitnow, jobicy.');
            return self::FAILURE;
        }

        $this->ensureBiomedicalCategory();
        $categories = $this->categoriesInImportOrder((string) ($this->option('category') ?? ''));

        if ($categories->isEmpty()) {
            $this->error('No matching job category found.');
            return self::FAILURE;
        }

        $this->info(sprintf(
            'Importing up to %d live jobs for %d categor%s. Biomedical Engineering is processed first.',
            $limit,
            $categories->count(),
            $categories->count() === 1 ? 'y' : 'ies'
        ));

        $this->warmSharedFeeds($sources);

        $summary = [];
        $totalSynced = 0;

        foreach ($categories as $category) {
            $this->newLine();
            $this->components->info("{$category->name}: finding live jobs");

            [$synced, $providers] = $this->importCategory($category, $limit, $sources);
            $totalSynced += $synced;

            $summary[] = [
                $category->name,
                $synced,
                $limit,
                $providers !== [] ? implode(', ', array_keys($providers)) : '-',
            ];

            if ($synced < $limit) {
                $this->components->warn("{$category->name}: found {$synced}/{$limit} unique live jobs. Re-run later for additional openings.");
            } else {
                $this->components->info("{$category->name}: {$synced}/{$limit} jobs synced");
            }
        }

        Cache::forget('jobCategories');
        Cache::forget('jobCategoriesJobsCount');
        Cache::forget('jobCategoriesAll');

        $this->newLine();
        $this->table(['Category', 'Synced', 'Target', 'Sources'], $summary);
        $this->info("Done. {$totalSynced} category job slots synced using {$this->requestCount} live HTTP requests.");
        $this->line('Apply Now uses the imported job source/application URL.');

        return self::SUCCESS;
    }

    private function importCategory(JobCategory $category, int $limit, array $sources): array
    {
        $queries = $this->queriesFor($category);
        $candidates = [];
        $seen = [];

        foreach ($sources as $source) {
            if ($source === 'himalayas') {
                continue;
            }

            foreach ($this->feedCache[$source] ?? [] as $job) {
                $normalized = $this->normalizeJob($source, $job);

                if ($normalized === null || ! $this->matchesCategory($category, $normalized)) {
                    continue;
                }

                $this->appendCandidate($candidates, $seen, $normalized);
            }
        }

        if (in_array('himalayas', $sources, true) && count($candidates) < ($limit * 2)) {
            foreach ($queries as $query) {
                try {
                    foreach ($this->fetchHimalayas($query, $category->slug === self::BIOMEDICAL_SLUG ? 2 : 1) as $job) {
                        $normalized = $this->normalizeJob('himalayas', $job);

                        if ($normalized === null || ! $this->matchesCategory($category, $normalized)) {
                            continue;
                        }

                        $this->appendCandidate($candidates, $seen, $normalized);
                    }
                } catch (Throwable $e) {
                    $this->components->warn("Himalayas failed for '{$query}': {$e->getMessage()}");
                }

                if (count($candidates) >= ($limit * 3)) {
                    break;
                }
            }
        }

        usort($candidates, static fn (array $a, array $b): int =>
            ($b['posted_at']?->getTimestamp() ?? 0) <=> ($a['posted_at']?->getTimestamp() ?? 0)
        );

        $synced = 0;
        $providers = [];

        foreach ($candidates as $candidate) {
            if ($synced >= $limit) {
                break;
            }

            $existing = JobListing::query()->where('apply_link', $candidate['apply_link'])->first();

            if ($existing && (int) $existing->job_category !== (int) $category->id) {
                continue;
            }

            if ($existing) {
                $existing->fill($candidate);
                $existing->job_category = $category->id;
                $existing->save();
            } else {
                $candidate['job_category'] = $category->id;
                JobListing::query()->create($candidate);
            }

            $providers[$candidate['publisher']] = true;
            $synced++;
        }

        return [$synced, $providers];
    }

    private function warmSharedFeeds(array $sources): void
    {
        foreach ($sources as $source) {
            try {
                $this->feedCache[$source] = match ($source) {
                    'remotive' => $this->fetchRemotiveFeed(),
                    'arbeitnow' => $this->fetchArbeitnowFeed(),
                    'jobicy' => $this->fetchJobicyFeed(),
                    default => [],
                };
            } catch (Throwable $e) {
                $this->feedCache[$source] = [];
                $this->components->warn("{$source} feed unavailable: {$e->getMessage()}");
            }
        }
    }

    private function fetchHimalayas(string $query, int $pages = 1): array
    {
        $jobs = [];

        for ($page = 1; $page <= $pages; $page++) {
            $response = $this->http()
                ->get('https://himalayas.app/jobs/api/search', [
                    'q' => $query,
                    'sort' => 'recent',
                    'page' => $page,
                ])
                ->throw()
                ->json();

            $pageJobs = $this->extractJobsArray($response);
            if ($pageJobs === []) {
                break;
            }

            $jobs = array_merge($jobs, $pageJobs);
        }

        return $jobs;
    }

    private function fetchRemotiveFeed(): array
    {
        $response = $this->http()
            ->get('https://remotive.com/api/remote-jobs')
            ->throw()
            ->json();

        return is_array($response['jobs'] ?? null) ? $response['jobs'] : [];
    }

    private function fetchArbeitnowFeed(): array
    {
        $jobs = [];

        foreach (['https://www.arbeitnow.com/api/job-board-api', 'https://www.arbeitnow.co.uk/api/job-board-api'] as $endpoint) {
            for ($page = 1; $page <= 5; $page++) {
                $response = $this->http()
                    ->get($endpoint, ['page' => $page])
                    ->throw()
                    ->json();

                $pageJobs = is_array($response['data'] ?? null) ? $response['data'] : [];
                if ($pageJobs === []) {
                    break;
                }

                $jobs = array_merge($jobs, $pageJobs);

                if (empty(data_get($response, 'links.next'))) {
                    break;
                }
            }
        }

        return $jobs;
    }

    private function fetchJobicyFeed(): array
    {
        $response = $this->http()
            ->get('https://jobicy.com/api/v2/remote-jobs', ['count' => 100])
            ->throw()
            ->json();

        return is_array($response['jobs'] ?? null) ? $response['jobs'] : [];
    }

    private function normalizeJob(string $source, array $job): ?array
    {
        return match ($source) {
            'himalayas' => $this->normalizeHimalayas($job),
            'remotive' => $this->normalizeRemotive($job),
            'arbeitnow' => $this->normalizeArbeitnow($job),
            'jobicy' => $this->normalizeJobicy($job),
            default => null,
        };
    }

    private function normalizeHimalayas(array $job): ?array
    {
        $title = (string) ($job['title'] ?? $job['jobTitle'] ?? '');
        $applyLink = (string) ($job['applicationLink'] ?? $job['application_link'] ?? $job['url'] ?? '');
        $company = (string) ($job['companyName'] ?? data_get($job, 'company.name') ?? 'Unknown employer');

        if ($title === '' || ! $this->validHttpUrl($applyLink)) {
            return null;
        }

        $locations = $job['locationRestrictions'] ?? [];
        $country = 'Remote';

        if (is_array($locations) && $locations !== []) {
            $names = array_map(static function ($location): string {
                return is_array($location) ? (string) ($location['name'] ?? '') : (string) $location;
            }, $locations);
            $names = array_values(array_filter($names));
            $country = $names !== [] ? implode(', ', $names) : 'Remote';
        }

        return $this->baseJob(
            'Himalayas',
            $title,
            $company,
            $applyLink,
            (string) ($job['description'] ?? ''),
            (string) ($job['employmentType'] ?? $job['employment_type'] ?? ''),
            true,
            null,
            null,
            $country,
            $job['pubDate'] ?? $job['publicationDate'] ?? null,
            (string) ($job['companyLogo'] ?? data_get($job, 'company.logo') ?? ''),
            (string) ($job['companyWebsite'] ?? data_get($job, 'company.website') ?? ''),
            $job['minSalary'] ?? $job['salaryMin'] ?? null,
            $job['maxSalary'] ?? $job['salaryMax'] ?? null,
            (string) ($job['currency'] ?? '')
        );
    }

    private function normalizeRemotive(array $job): ?array
    {
        $title = (string) ($job['title'] ?? '');
        $applyLink = (string) ($job['url'] ?? '');

        if ($title === '' || ! $this->validHttpUrl($applyLink)) {
            return null;
        }

        [$minSalary, $maxSalary, $currency] = $this->parseSalaryText((string) ($job['salary'] ?? ''));

        return $this->baseJob(
            'Remotive',
            $title,
            (string) ($job['company_name'] ?? 'Unknown employer'),
            $applyLink,
            (string) ($job['description'] ?? ''),
            (string) ($job['job_type'] ?? ''),
            true,
            null,
            null,
            (string) ($job['candidate_required_location'] ?? 'Remote'),
            $job['publication_date'] ?? null,
            (string) ($job['company_logo'] ?? ''),
            '',
            $minSalary,
            $maxSalary,
            $currency
        );
    }

    private function normalizeArbeitnow(array $job): ?array
    {
        $title = (string) ($job['title'] ?? '');
        $applyLink = (string) ($job['url'] ?? '');

        if ($title === '' || ! $this->validHttpUrl($applyLink)) {
            return null;
        }

        $types = $job['job_types'] ?? [];
        $employmentType = is_array($types) ? implode(', ', array_slice($types, 0, 2)) : (string) $types;

        return $this->baseJob(
            'Arbeitnow',
            $title,
            (string) ($job['company_name'] ?? 'Unknown employer'),
            $applyLink,
            (string) ($job['description'] ?? ''),
            $employmentType,
            (bool) ($job['remote'] ?? false),
            (string) ($job['location'] ?? '') ?: null,
            null,
            null,
            isset($job['created_at']) && is_numeric($job['created_at']) ? Carbon::createFromTimestamp((int) $job['created_at']) : null
        );
    }

    private function normalizeJobicy(array $job): ?array
    {
        $title = (string) ($job['jobTitle'] ?? $job['title'] ?? '');
        $applyLink = (string) ($job['url'] ?? $job['jobUrl'] ?? '');

        if ($title === '' || ! $this->validHttpUrl($applyLink)) {
            return null;
        }

        return $this->baseJob(
            'Jobicy',
            $title,
            (string) ($job['companyName'] ?? $job['company'] ?? 'Unknown employer'),
            $applyLink,
            (string) ($job['jobDescription'] ?? $job['description'] ?? ''),
            (string) ($job['jobType'] ?? $job['employmentType'] ?? ''),
            true,
            null,
            null,
            (string) ($job['jobGeo'] ?? $job['location'] ?? 'Remote'),
            $job['pubDate'] ?? $job['publicationDate'] ?? null,
            (string) ($job['companyLogo'] ?? ''),
            '',
            $job['annualSalaryMin'] ?? $job['salaryMin'] ?? null,
            $job['annualSalaryMax'] ?? $job['salaryMax'] ?? null,
            (string) ($job['salaryCurrency'] ?? $job['currency'] ?? '')
        );
    }

    private function baseJob(
        string $publisher,
        string $title,
        string $company,
        string $applyLink,
        string $description,
        string $employmentType = '',
        bool $remote = false,
        ?string $city = null,
        ?string $state = null,
        ?string $country = null,
        mixed $postedAt = null,
        string $employerLogo = '',
        string $employerWebsite = '',
        mixed $minSalary = null,
        mixed $maxSalary = null,
        string $salaryCurrency = ''
    ): array {
        $cleanDescription = $this->cleanDescription($description);
        $cleanDescription = trim($cleanDescription."\n\nSource: {$publisher}");

        return [
            'employer_name' => Str::limit(trim($company), 250, ''),
            'employer_logo' => $this->validHttpUrl($employerLogo) ? $employerLogo : null,
            'employer_website' => $this->validHttpUrl($employerWebsite) ? $employerWebsite : null,
            'employer_company_type' => null,
            'publisher' => $publisher,
            'employment_type' => Str::limit($this->humanizeEmploymentType($employmentType), 250, ''),
            'job_title' => Str::limit(trim($title), 250, ''),
            'apply_link' => trim($applyLink),
            'description' => Str::limit($cleanDescription, 60000, ''),
            'is_remote' => $remote,
            'city' => $city ? Str::limit(trim($city), 250, '') : null,
            'state' => $state ? Str::limit(trim($state), 250, '') : null,
            'country' => $country ? Str::limit(trim($country), 250, '') : null,
            'posted_at' => $this->parseDate($postedAt),
            'min_salary' => $this->numericSalary($minSalary),
            'max_salary' => $this->numericSalary($maxSalary),
            'salary_currency' => $salaryCurrency !== '' ? Str::limit($salaryCurrency, 20, '') : null,
            'salary_period' => null,
            'benefits' => null,
            'qualifications' => null,
            'responsibilities' => null,
            'required_experience' => null,
        ];
    }

    private function queriesFor(JobCategory $category): array
    {
        $aliases = [
            self::BIOMEDICAL_SLUG => ['biomedical engineer', 'biomedical engineering', 'medical device engineer', 'clinical engineer', 'bioengineer'],
            'laravel' => ['Laravel developer', 'Laravel PHP'],
            'symfony' => ['Symfony developer', 'Symfony PHP'],
            'wordpress' => ['WordPress developer', 'WordPress engineer'],
            'vuejs' => ['Vue.js developer', 'Vue frontend'],
            'react' => ['React developer', 'React.js engineer'],
            'angular' => ['Angular developer', 'Angular engineer'],
            'django' => ['Django developer', 'Django Python'],
            'flask' => ['Flask developer', 'Flask Python'],
            'express' => ['Express.js developer', 'Node.js Express'],
            'spring' => ['Spring Boot developer', 'Java Spring'],
            'ruby-on-rails' => ['Ruby on Rails developer', 'Rails engineer'],
            'nodejs' => ['Node.js developer', 'Node engineer'],
            'python' => ['Python developer', 'Python engineer'],
            'aspnet' => ['ASP.NET developer', '.NET developer'],
        ];

        $queries = $aliases[Str::lower($category->slug)] ?? [
            trim((string) $category->query_name),
            trim((string) $category->name),
        ];

        return array_values(array_unique(array_filter($queries)));
    }

    private function matchesCategory(JobCategory $category, array $job): bool
    {
        $haystack = Str::lower(($job['job_title'] ?? '').' '.($job['description'] ?? ''));
        $slug = Str::lower($category->slug);

        $keywords = match ($slug) {
            self::BIOMEDICAL_SLUG => ['biomedical', 'medical device', 'clinical engineer', 'biomechanical', 'bioengineer', 'bio-engineer', 'medical equipment'],
            'laravel' => ['laravel'],
            'symfony' => ['symfony'],
            'wordpress' => ['wordpress'],
            'vuejs' => ['vue.js', 'vuejs', 'vue 2', 'vue 3'],
            'react' => ['react.js', 'reactjs', 'react developer', 'react engineer'],
            'angular' => ['angular'],
            'django' => ['django'],
            'flask' => ['flask'],
            'express' => ['express.js', 'expressjs', 'node express'],
            'spring' => ['spring boot', 'spring framework', 'java spring'],
            'ruby-on-rails' => ['ruby on rails', 'rails developer', 'rails engineer'],
            'nodejs' => ['node.js', 'nodejs', 'node developer', 'node engineer'],
            'python' => ['python'],
            'aspnet' => ['asp.net', '.net developer', '.net engineer', 'dotnet'],
            default => [Str::lower(trim((string) $category->query_name))],
        };

        foreach (array_filter($keywords) as $keyword) {
            if (str_contains($haystack, $keyword)) {
                return true;
            }
        }

        return false;
    }

    private function ensureBiomedicalCategory(): JobCategory
    {
        $category = JobCategory::query()->firstOrNew(['slug' => self::BIOMEDICAL_SLUG]);
        $category->name = self::BIOMEDICAL_NAME;
        $category->query_name = 'Biomedical Engineer';
        $category->page = 2;
        $category->num_page = 3;
        $category->timeframe = 'month';
        $category->category_image = 'assets/images/categories/biomedical.svg';
        $category->save();

        return $category;
    }

    private function categoriesInImportOrder(string $onlyCategory): Collection
    {
        $categories = JobCategory::query()->get();

        if ($onlyCategory !== '') {
            $needle = Str::lower(trim($onlyCategory));
            $categories = $categories->filter(static function (JobCategory $category) use ($needle): bool {
                return in_array($needle, [
                    Str::lower($category->name),
                    Str::lower($category->slug),
                    Str::lower((string) $category->query_name),
                ], true);
            });
        }

        return $categories
            ->sortBy(static fn (JobCategory $category): string => sprintf(
                '%d-%010d',
                $category->slug === self::BIOMEDICAL_SLUG ? 0 : 1,
                $category->id
            ))
            ->values();
    }

    private function parseSources(string $sources): array
    {
        $supported = ['himalayas', 'remotive', 'arbeitnow', 'jobicy'];

        return collect(explode(',', Str::lower($sources)))
            ->map(static fn (string $source): string => trim($source))
            ->filter(static fn (string $source): bool => in_array($source, $supported, true))
            ->unique()
            ->values()
            ->all();
    }

    private function http(): PendingRequest
    {
        $this->requestCount++;

        return Http::acceptJson()
            ->withUserAgent('BestWayJobs/1.0 (+live-job-import)')
            ->timeout(25)
            ->connectTimeout(10)
            ->retry([400, 900], throw: false);
    }

    private function extractJobsArray(mixed $response): array
    {
        if (! is_array($response)) {
            return [];
        }

        foreach (['jobs', 'data', 'results'] as $key) {
            if (isset($response[$key]) && is_array($response[$key])) {
                return array_values(array_filter($response[$key], 'is_array'));
            }
        }

        return array_is_list($response) ? array_values(array_filter($response, 'is_array')) : [];
    }

    private function appendCandidate(array &$candidates, array &$seen, array $job): void
    {
        $key = $this->normalizeUrlForDedup($job['apply_link']);

        if ($key === '' || isset($seen[$key])) {
            return;
        }

        $seen[$key] = true;
        $candidates[] = $job;
    }

    private function cleanDescription(string $description): string
    {
        $plain = html_entity_decode(strip_tags($description), ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $plain = preg_replace('/[ \t]+/', ' ', $plain) ?? $plain;
        $plain = preg_replace('/\R{3,}/', "\n\n", $plain) ?? $plain;

        return Str::limit(trim($plain), 59800, '');
    }

    private function parseDate(mixed $value): Carbon
    {
        if ($value instanceof Carbon) {
            return $value;
        }

        if (is_numeric($value)) {
            return Carbon::createFromTimestamp((int) $value);
        }

        try {
            return $value ? Carbon::parse((string) $value) : now();
        } catch (Throwable) {
            return now();
        }
    }

    private function parseSalaryText(string $salary): array
    {
        if ($salary === '') {
            return [null, null, ''];
        }

        $currency = str_contains($salary, '$') ? 'USD' : (str_contains($salary, '€') ? 'EUR' : (str_contains($salary, '£') ? 'GBP' : ''));
        preg_match_all('/\d[\d,.]*/', $salary, $matches);

        $numbers = array_values(array_filter(array_map(static function (string $number): ?float {
            $number = str_replace([',', ' '], '', $number);
            return is_numeric($number) ? (float) $number : null;
        }, $matches[0] ?? []), static fn ($value): bool => $value !== null));

        return [$numbers[0] ?? null, $numbers[1] ?? ($numbers[0] ?? null), $currency];
    }

    private function numericSalary(mixed $value): ?float
    {
        if ($value === null || $value === '') {
            return null;
        }

        if (is_numeric($value)) {
            return min((float) $value, 99999999.99);
        }

        $clean = preg_replace('/[^\d.]/', '', (string) $value);

        return $clean !== '' && is_numeric($clean) ? min((float) $clean, 99999999.99) : null;
    }

    private function humanizeEmploymentType(string $type): string
    {
        return $type === ''
            ? 'Not specified'
            : Str::of($type)->replace(['_', '-'], ' ')->squish()->title()->toString();
    }

    private function validHttpUrl(string $url): bool
    {
        if ($url === '' || filter_var($url, FILTER_VALIDATE_URL) === false) {
            return false;
        }

        return in_array(Str::lower((string) parse_url($url, PHP_URL_SCHEME)), ['http', 'https'], true);
    }

    private function normalizeUrlForDedup(string $url): string
    {
        $parts = parse_url($url);

        if (! is_array($parts) || empty($parts['host'])) {
            return Str::lower(trim($url));
        }

        return Str::lower(($parts['host'] ?? '').rtrim((string) ($parts['path'] ?? ''), '/'));
    }
}
