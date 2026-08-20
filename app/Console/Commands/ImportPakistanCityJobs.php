<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Caches\JobListingCache;
use App\Models\Country;
use App\Models\JobCategory;
use App\Models\JobListing;
use App\Support\JobDescriptionSanitizer;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Throwable;

class ImportPakistanCityJobs extends Command
{
    protected $signature = 'jobs:import-pakistan-cities
                            {--limit=10 : Maximum jobs to sync per category}
                            {--city= : Import only one Pakistan city}
                            {--max-details=220 : Maximum individual NJP job pages to inspect}';

    protected $description = 'Import latest Pakistan jobs with city diversity and broader job categories from the National Jobs Portal.';

    private const SOURCE = 'National Jobs Portal (NJP)';
    private const BASE_URL = 'https://www.njp.gov.pk';

    private int $requestCount = 0;
    private array $htmlCache = [];

    /** @var array<string, string> */
    private array $cities = [
        'Islamabad' => 'Federal Capital (ICT)',
        'Rawalpindi' => 'Punjab',
        'Lahore' => 'Punjab',
        'Karachi' => 'Sindh',
        'Faisalabad' => 'Punjab',
        'Multan' => 'Punjab',
        'Peshawar' => 'Khyber Pakhtunkhwa',
        'Quetta' => 'Balochistan',
        'Hyderabad' => 'Sindh',
        'Sialkot' => 'Punjab',
        'Gujranwala' => 'Punjab',
        'Bahawalpur' => 'Punjab',
        'Sukkur' => 'Sindh',
        'Abbottabad' => 'Khyber Pakhtunkhwa',
        'Muzaffarabad' => 'Azad Jammu & Kashmir',
        'Gilgit' => 'Gilgit-Baltistan',
        'Skardu' => 'Gilgit-Baltistan',
        'Gwadar' => 'Balochistan',
        'Tando Muhammad Khan' => 'Sindh',
    ];

    /** @var array<string, array<string, mixed>> */
    private array $categoryDefinitions = [
        'biomedical-engineering' => [
            'name' => 'Biomedical Engineering',
            'query_name' => 'Biomedical Engineer',
            'keywords' => ['biomedical', 'bioengineer', 'clinical engineer', 'medical equipment', 'medical device', 'instrumentation engineer'],
            'image' => 'assets/images/categories/biomedical.svg',
        ],
        'healthcare-medical' => [
            'name' => 'Healthcare & Medical',
            'query_name' => 'Healthcare Medical',
            'keywords' => ['medical', 'health', 'hospital', 'doctor', 'nurse', 'pharmac', 'radiology', 'surgery', 'clinical', 'laboratory'],
            'image' => 'assets/images/categories/biomedical.svg',
        ],
        'information-technology-ai' => [
            'name' => 'Information Technology & AI',
            'query_name' => 'Information Technology AI',
            'keywords' => ['information technology', 'artificial intelligence', 'machine learning', 'cyber', 'network', 'it operations', 'technology officer', 'digital'],
            'image' => 'assets/images/categories/jobs.svg',
        ],
        'software-development' => [
            'name' => 'Software Development',
            'query_name' => 'Software Developer Engineer',
            'keywords' => ['software engineer', 'software developer', 'developer', 'programmer', 'web developer', 'mobile developer', 'full stack', 'backend', 'frontend'],
            'image' => 'assets/images/categories/jobs.svg',
        ],
        'data-analytics' => [
            'name' => 'Data & Analytics',
            'query_name' => 'Data Analytics',
            'keywords' => ['data analyst', 'data engineer', 'data science', 'analytics', 'business intelligence', 'statistic', 'database'],
            'image' => 'assets/images/categories/jobs.svg',
        ],
        'engineering-technical' => [
            'name' => 'Engineering & Technical',
            'query_name' => 'Engineering Technical',
            'keywords' => ['engineer', 'engineering', 'electrical', 'mechanical', 'civil', 'electronics', 'telecom', 'technical', 'infrastructure', 'quantity'],
            'image' => 'assets/images/categories/jobs.svg',
        ],
        'education-teaching' => [
            'name' => 'Education & Teaching',
            'query_name' => 'Education Teaching',
            'keywords' => ['teacher', 'teaching', 'lecturer', 'professor', 'education', 'instructor', 'curriculum', 'academic'],
            'image' => 'assets/images/categories/jobs.svg',
        ],
        'finance-accounts' => [
            'name' => 'Finance & Accounts',
            'query_name' => 'Finance Accounts',
            'keywords' => ['finance', 'accounts', 'accounting', 'accountant', 'audit', 'auditor', 'treasury', 'financial'],
            'image' => 'assets/images/categories/jobs.svg',
        ],
        'banking-business-development' => [
            'name' => 'Banking & Business Development',
            'query_name' => 'Banking Business Development',
            'keywords' => ['bank', 'banking', 'relationship manager', 'business development', 'commercial', 'credit', 'portfolio'],
            'image' => 'assets/images/categories/jobs.svg',
        ],
        'administration-hr' => [
            'name' => 'Administration & HR',
            'query_name' => 'Administration Human Resources',
            'keywords' => ['administration', 'administrative', 'human resources', 'recruitment', 'talent acquisition', 'office management', 'hr manager', 'admin officer'],
            'image' => 'assets/images/categories/jobs.svg',
        ],
        'legal-compliance' => [
            'name' => 'Legal & Compliance',
            'query_name' => 'Legal Compliance',
            'keywords' => ['legal', 'lawyer', 'counsel', 'advocate', 'compliance', 'regulatory', 'law officer'],
            'image' => 'assets/images/categories/jobs.svg',
        ],
        'sales-marketing' => [
            'name' => 'Sales & Marketing',
            'query_name' => 'Sales Marketing',
            'keywords' => ['sales', 'marketing', 'brand', 'business development', 'market research', 'commercial officer'],
            'image' => 'assets/images/categories/jobs.svg',
        ],
        'media-communications' => [
            'name' => 'Media & Communications',
            'query_name' => 'Media Communications',
            'keywords' => ['communications', 'communication', 'media', 'public relations', 'photographer', 'content', 'social media'],
            'image' => 'assets/images/categories/jobs.svg',
        ],
        'project-program-management' => [
            'name' => 'Project & Program Management',
            'query_name' => 'Project Program Management',
            'keywords' => ['project director', 'project manager', 'program manager', 'programme manager', 'project coordinator', 'pmu', 'monitoring', 'planning specialist'],
            'image' => 'assets/images/categories/jobs.svg',
        ],
        'operations-procurement' => [
            'name' => 'Operations & Procurement',
            'query_name' => 'Operations Procurement',
            'keywords' => ['operations', 'procurement', 'supply chain', 'store keeper', 'logistics', 'purchasing', 'inventory', 'warehouse'],
            'image' => 'assets/images/categories/jobs.svg',
        ],
        'research-academia' => [
            'name' => 'Research & Academia',
            'query_name' => 'Research Academia',
            'keywords' => ['research', 'researcher', 'research associate', 'research officer', 'scientist', 'academic research'],
            'image' => 'assets/images/categories/jobs.svg',
        ],
        'customer-service-support' => [
            'name' => 'Customer Service & Support',
            'query_name' => 'Customer Service Support',
            'keywords' => ['customer service', 'customer support', 'help desk', 'support staff', 'call center', 'service desk'],
            'image' => 'assets/images/categories/jobs.svg',
        ],
        'clerical-office-support' => [
            'name' => 'Clerical & Office Support',
            'query_name' => 'Clerical Office Support',
            'keywords' => ['clerk', 'stenographer', 'data entry operator', 'office assistant', 'assistant', 'receptionist', 'secretary'],
            'image' => 'assets/images/categories/jobs.svg',
        ],
    ];

    public function handle(): int
    {
        config()->set('scout.driver', 'database');

        $limit = max(1, min(20, (int) $this->option('limit')));
        $maxDetails = max(40, min(500, (int) $this->option('max-details')));
        $requestedCity = trim((string) $this->option('city'));

        if ($requestedCity !== '' && ! array_key_exists($requestedCity, $this->cities)) {
            $matched = collect(array_keys($this->cities))->first(
                fn (string $city): bool => Str::lower($city) === Str::lower($requestedCity)
            );

            if ($matched === null) {
                $this->error('Unknown city. Supported examples: '.implode(', ', array_slice(array_keys($this->cities), 0, 10)).'.');
                return self::FAILURE;
            }

            $requestedCity = $matched;
        }

        $pakistan = Country::query()->updateOrCreate(
            ['code' => 'PK'],
            ['name' => 'Pakistan', 'is_active' => true]
        );

        $categories = $this->ensureCategories($pakistan);
        $this->info(sprintf(
            'Discovering latest Pakistan jobs for %d categories%s. City diversity is enabled.',
            $categories->count(),
            $requestedCity !== '' ? " in {$requestedCity}" : ''
        ));

        $links = $this->discoverLinks($requestedCity);

        if ($links === []) {
            $this->error('No NJP job links were discovered.');
            return self::FAILURE;
        }

        $this->line(sprintf('Discovered %d unique NJP job links; inspecting up to %d.', count($links), $maxDetails));

        $candidatesByCategory = [];
        $inspected = 0;

        foreach ($links as $url) {
            if ($inspected >= $maxDetails) {
                break;
            }

            $inspected++;
            $job = $this->fetchJob($url);

            if ($job === null) {
                continue;
            }

            if ($job['expired_at'] instanceof Carbon && $job['expired_at']->lt(now()->startOfDay())) {
                continue;
            }

            if ($requestedCity !== '' && ! $this->jobMatchesCity($job, $requestedCity)) {
                continue;
            }

            $categorySlug = $this->bestCategorySlug($job);

            if ($categorySlug === null) {
                continue;
            }

            $candidatesByCategory[$categorySlug][] = $job;
        }

        $summary = [];
        $totalSynced = 0;
        $allCities = [];

        foreach ($categories as $category) {
            $slug = (string) $category->slug;
            $candidates = $this->cityDiverseOrder($candidatesByCategory[$slug] ?? []);
            $synced = 0;
            $categoryCities = [];

            foreach ($candidates as $candidate) {
                if ($synced >= $limit) {
                    break;
                }

                try {
                    $candidate['job_category'] = $category->id;
                    unset($candidate['_cities']);

                    $existing = JobListing::withoutGlobalScopes()
                        ->where('apply_link', $candidate['apply_link'])
                        ->first();

                    if ($existing) {
                        $existing->fill($candidate);
                        $existing->job_category = $category->id;
                        $existing->save();
                    } else {
                        JobListing::query()->create($candidate);
                    }

                    $cityLabel = trim((string) ($candidate['city'] ?? '')) ?: 'Pakistan';
                    $categoryCities[$cityLabel] = true;
                    $allCities[$cityLabel] = true;
                    $synced++;
                    $totalSynced++;
                } catch (Throwable $e) {
                    $this->components->warn(
                        "Skipped DB save for {$candidate['job_title']}: {$e->getMessage()}"
                    );
                }
            }

            $summary[] = [
                $category->name,
                $synced,
                $limit,
                $categoryCities !== [] ? implode(', ', array_slice(array_keys($categoryCities), 0, 4)) : '-',
            ];
        }

        foreach (['jobCategories', 'jobCategoriesJobsCount', 'jobCategoriesAll'] as $key) {
            Cache::forget($key);
        }
        JobListingCache::invalidate();

        $this->newLine();
        $this->table(['Category', 'Synced', 'Target', 'Cities (sample)'], $summary);
        $this->info(sprintf(
            'Done. %d job slots synced across %d distinct city/location labels using %d HTTP requests.',
            $totalSynced,
            count($allCities),
            $this->requestCount
        ));
        $this->line('Apply Now keeps the real NJP application/detail URL.');

        return self::SUCCESS;
    }

    private function ensureCategories(Country $pakistan): Collection
    {
        $categories = collect();

        foreach ($this->categoryDefinitions as $slug => $definition) {
            $category = JobCategory::query()->firstOrNew(['slug' => $slug]);
            $category->name = (string) $definition['name'];
            $category->query_name = (string) $definition['query_name'];
            $category->page = 1;
            $category->num_page = 10;
            $category->timeframe = 'week';
            $category->category_image = (string) $definition['image'];
            $category->save();
            $category->countries()->syncWithoutDetaching([$pakistan->id]);
            $categories->push($category);
        }

        return $categories;
    }

    private function discoverLinks(string $requestedCity): array
    {
        $seen = [];
        $ordered = [];

        $addPage = function (string $url) use (&$seen, &$ordered): void {
            try {
                $html = $this->fetchHtml($url);
            } catch (Throwable $e) {
                $this->components->warn("NJP listing unavailable: {$e->getMessage()}");
                return;
            }

            foreach ($this->extractJobLinks($html) as $link) {
                if (isset($seen[$link])) {
                    continue;
                }

                $seen[$link] = true;
                $ordered[] = $link;
            }
        };

        $cities = $requestedCity !== '' ? [$requestedCity] : array_keys($this->cities);

        foreach ($cities as $city) {
            foreach ([1, 2] as $page) {
                $addPage(self::BASE_URL.'/jobs/live?location='.rawurlencode($city).'&page='.$page);
            }
        }

        for ($page = 1; $page <= 6; $page++) {
            $addPage(self::BASE_URL.'/jobs/live?page='.$page);
        }

        return $ordered;
    }

    private function extractJobLinks(string $html): array
    {
        preg_match_all('~href\s*=\s*["\']([^"\']*/jobs/\d+(?:[^"\']*)?)["\']~i', $html, $matches);
        $links = [];

        foreach ($matches[1] ?? [] as $href) {
            $href = html_entity_decode((string) $href, ENT_QUOTES | ENT_HTML5, 'UTF-8');
            $path = parse_url($href, PHP_URL_PATH);

            if (! is_string($path) || preg_match('~^/jobs/\d+$~', $path) !== 1) {
                continue;
            }

            $links[self::BASE_URL.$path] = true;
        }

        return array_keys($links);
    }

    private function fetchJob(string $url): ?array
    {
        try {
            $html = $this->fetchHtml($url);
        } catch (Throwable $e) {
            $this->components->warn("NJP job page failed ({$url}): {$e->getMessage()}");
            return null;
        }

        $posting = $this->extractStructuredJobPosting($html);
        $body = $this->bodyText($html);
        $contentArea = $this->contentArea($body);

        $title = $this->text($posting['title'] ?? null) ?: $this->firstHeading($html);

        if ($title === '') {
            return null;
        }

        $company = $this->text(data_get($posting, 'hiringOrganization.name'));
        if ($company === '') {
            $company = $this->companyFromBody($title, $contentArea) ?: 'Government of Pakistan';
        }

        $description = $this->text($posting['description'] ?? null);
        if (mb_strlen(strip_tags($description)) < 60) {
            $description = $this->descriptionFromBody($contentArea);
        }
        if ($description === '') {
            $description = $this->metaContent($html, 'description');
        }

        $description = JobDescriptionSanitizer::sanitize($description, $title, $company);

        $postedAt = $this->parseDate($posting['datePosted'] ?? null)
            ?? $this->dateFromBody($contentArea, ['Posted']);
        $expiredAt = $this->parseDate($posting['validThrough'] ?? null)
            ?? $this->dateFromBody($contentArea, ['Application Deadline', 'Deadline', 'Available Till', 'Available till']);

        [$structuredCity, $structuredState] = $this->locationFromPosting($posting);
        $detectedCities = $this->detectCities($title.' '.$company.' '.$contentArea);

        if ($structuredCity !== null && ! in_array($structuredCity, $detectedCities, true)) {
            array_unshift($detectedCities, $structuredCity);
        }

        $detectedCities = array_values(array_unique($detectedCities));
        $city = $structuredCity;
        $state = $structuredState;

        if ($city === null && $detectedCities !== []) {
            $city = implode(', ', array_slice($detectedCities, 0, 3));
        }

        if ($state === null && $detectedCities !== []) {
            $states = [];
            foreach ($detectedCities as $detectedCity) {
                if (isset($this->cities[$detectedCity])) {
                    $states[$this->cities[$detectedCity]] = true;
                }
            }
            $state = $states !== [] ? implode(', ', array_keys($states)) : null;
        }

        [$minSalary, $maxSalary, $currency, $period] = $this->salaryFromPosting($posting);
        $qualifications = $this->listValue($posting['qualifications'] ?? $posting['educationRequirements'] ?? null);
        $responsibilities = $this->listValue($posting['responsibilities'] ?? null);
        $benefits = $this->listValue($posting['jobBenefits'] ?? null);

        return [
            'employer_name' => Str::limit($company, 250, ''),
            'employer_logo' => $this->urlOrNull(data_get($posting, 'hiringOrganization.logo')),
            'employer_website' => $this->urlOrNull(data_get($posting, 'hiringOrganization.sameAs')),
            'employer_company_type' => 'Government / Public Sector',
            'publisher' => self::SOURCE,
            'employment_type' => $this->normalizeEmploymentType($this->text($posting['employmentType'] ?? null).' '.$contentArea),
            'job_title' => Str::limit($title, 250, ''),
            'apply_link' => $url,
            'description' => $description,
            'is_remote' => false,
            'city' => $city ? Str::limit($city, 250, '') : null,
            'state' => $state ? Str::limit($state, 250, '') : null,
            'country' => 'Pakistan',
            'google_link' => null,
            'posted_at' => $postedAt ?? now(),
            'expired_at' => $expiredAt,
            'min_salary' => $minSalary,
            'max_salary' => $maxSalary,
            'salary_currency' => $currency,
            'salary_period' => $period,
            'benefits' => $benefits !== [] ? $benefits : null,
            'qualifications' => $qualifications !== [] ? $qualifications : null,
            'responsibilities' => $responsibilities !== [] ? $responsibilities : null,
            'skills' => null,
            'required_experience' => $this->experienceMonths($posting, $contentArea),
            '_cities' => $detectedCities,
        ];
    }

    private function bestCategorySlug(array $job): ?string
    {
        $title = Str::lower($this->text($job['job_title'] ?? null));
        $description = Str::lower(strip_tags($this->text($job['description'] ?? null)));
        $bestSlug = null;
        $bestScore = 0;

        foreach ($this->categoryDefinitions as $slug => $definition) {
            $score = 0;

            foreach ($definition['keywords'] as $keyword) {
                $keyword = Str::lower((string) $keyword);
                if ($keyword === '') {
                    continue;
                }
                if (str_contains($title, $keyword)) {
                    $score += 5;
                }
                if (str_contains($description, $keyword)) {
                    $score += 1;
                }
            }

            if ($score > $bestScore) {
                $bestScore = $score;
                $bestSlug = $slug;
            }
        }

        return $bestScore > 0 ? $bestSlug : null;
    }

    private function cityDiverseOrder(array $jobs): array
    {
        usort($jobs, static fn (array $a, array $b): int =>
            ($b['posted_at']?->getTimestamp() ?? 0) <=> ($a['posted_at']?->getTimestamp() ?? 0)
        );

        $buckets = [];
        foreach ($jobs as $job) {
            $cities = $job['_cities'] ?? [];
            $key = is_array($cities) && $cities !== [] ? (string) $cities[0] : ((string) ($job['city'] ?? '') ?: 'Pakistan');
            $buckets[$key][] = $job;
        }

        $ordered = [];
        while ($buckets !== []) {
            foreach (array_keys($buckets) as $key) {
                if ($buckets[$key] === []) {
                    unset($buckets[$key]);
                    continue;
                }
                $ordered[] = array_shift($buckets[$key]);
                if ($buckets[$key] === []) {
                    unset($buckets[$key]);
                }
            }
        }

        return $ordered;
    }

    private function jobMatchesCity(array $job, string $requestedCity): bool
    {
        $cities = $job['_cities'] ?? [];
        if (is_array($cities)) {
            foreach ($cities as $city) {
                if (Str::lower((string) $city) === Str::lower($requestedCity)) {
                    return true;
                }
            }
        }
        return str_contains(Str::lower((string) ($job['city'] ?? '')), Str::lower($requestedCity));
    }

    private function detectCities(string $text): array
    {
        $found = [];
        $normalized = Str::lower($text);
        foreach (array_keys($this->cities) as $city) {
            if (str_contains($normalized, Str::lower($city))) {
                $found[] = $city;
            }
        }
        return $found;
    }

    private function contentArea(string $body): string
    {
        $cutoffs = [];
        foreach (['About Employer', 'Share This Job', 'National Jobs Portal', 'Get In Touch'] as $marker) {
            $pos = stripos($body, $marker);
            if ($pos !== false && $pos > 100) {
                $cutoffs[] = $pos;
            }
        }
        return $cutoffs !== [] ? substr($body, 0, min($cutoffs)) : $body;
    }

    private function descriptionFromBody(string $body): string
    {
        $eligibility = stripos($body, 'Eligibility Criteria');
        $searchArea = $eligibility !== false ? substr($body, 0, $eligibility) : $body;
        $start = strripos($searchArea, 'Job Description');
        if ($start === false) {
            return '';
        }
        $start += strlen('Job Description');
        $end = $eligibility !== false ? $eligibility : strlen($body);
        return trim(substr($body, $start, max(0, $end - $start)));
    }

    private function fetchHtml(string $url): string
    {
        if (isset($this->htmlCache[$url])) {
            return $this->htmlCache[$url];
        }
        $this->requestCount++;
        $html = $this->http()->get($url)->throw()->body();
        return $this->htmlCache[$url] = $html;
    }

    private function http(): PendingRequest
    {
        return Http::withHeaders([
                'Accept' => 'text/html,application/xhtml+xml',
                'Accept-Language' => 'en-US,en;q=0.9',
            ])
            ->withUserAgent('Mozilla/5.0 (compatible; BestWayJobs/1.2; +Pakistan-city-job-import)')
            ->timeout(30)
            ->connectTimeout(10)
            ->retry([500, 1200], throw: false);
    }

    private function extractStructuredJobPosting(string $html): array
    {
        preg_match_all('~<script[^>]*type=["\']application/ld\+json["\'][^>]*>(.*?)</script>~is', $html, $matches);
        foreach ($matches[1] ?? [] as $json) {
            $decoded = json_decode(html_entity_decode(trim((string) $json), ENT_QUOTES | ENT_HTML5, 'UTF-8'), true);
            $posting = $this->findJobPosting($decoded);
            if ($posting !== null) {
                return $posting;
            }
        }
        return [];
    }

    private function findJobPosting(mixed $node): ?array
    {
        if (! is_array($node)) {
            return null;
        }
        $type = $node['@type'] ?? null;
        foreach (is_array($type) ? $type : [$type] as $candidateType) {
            if (is_string($candidateType) && Str::lower($candidateType) === 'jobposting') {
                return $node;
            }
        }
        foreach ($node as $child) {
            if (! is_array($child)) {
                continue;
            }
            $found = $this->findJobPosting($child);
            if ($found !== null) {
                return $found;
            }
        }
        return null;
    }

    private function bodyText(string $html): string
    {
        $html = preg_replace('~<(script|style)\b[^>]*>.*?</\1>~is', "\n", $html) ?? $html;
        $plain = html_entity_decode(strip_tags($html), ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $plain = preg_replace('/[ \t]+/', ' ', $plain) ?? $plain;
        $plain = preg_replace('/\R{2,}/', "\n", $plain) ?? $plain;
        return trim($plain);
    }

    private function firstHeading(string $html): string
    {
        if (preg_match('~<h1[^>]*>(.*?)</h1>~is', $html, $match) !== 1) {
            return '';
        }
        return trim(html_entity_decode(strip_tags($match[1]), ENT_QUOTES | ENT_HTML5, 'UTF-8'));
    }

    private function companyFromBody(string $title, string $body): string
    {
        $pattern = '/'.preg_quote($title, '/').'\s+(.{2,180}?)\s+Government of Pakistan/is';
        return preg_match($pattern, $body, $match) === 1
            ? trim(preg_replace('/\s+/', ' ', $match[1]) ?? $match[1])
            : '';
    }

    private function metaContent(string $html, string $name): string
    {
        $quoted = preg_quote($name, '~');
        return preg_match('~<meta[^>]+(?:name|property)=["\']'.$quoted.'["\'][^>]+content=["\']([^"\']*)["\'][^>]*>~i', $html, $match) === 1
            ? trim(html_entity_decode($match[1], ENT_QUOTES | ENT_HTML5, 'UTF-8'))
            : '';
    }

    private function dateFromBody(string $body, array $labels): ?Carbon
    {
        foreach ($labels as $label) {
            if (preg_match('/'.preg_quote($label, '/').'\s*:?\s*(\d{1,2}\s+[A-Za-z]{3,9}\s+\d{4})/i', $body, $match) === 1) {
                return $this->parseDate($match[1]);
            }
        }
        return null;
    }

    private function parseDate(mixed $value): ?Carbon
    {
        $text = $this->text($value);
        if ($text === '') {
            return null;
        }
        try {
            return Carbon::parse($text);
        } catch (Throwable) {
            return null;
        }
    }

    private function normalizeEmploymentType(string $value): string
    {
        $value = Str::lower($value);
        if (str_contains($value, 'part-time') || str_contains($value, 'part time')) {
            return 'parttime';
        }
        if (str_contains($value, 'contract') || str_contains($value, 'intern') || str_contains($value, 'temporary') || str_contains($value, 'consultant')) {
            return 'contractor';
        }
        return 'fulltime';
    }

    private function locationFromPosting(array $posting): array
    {
        $location = $posting['jobLocation'] ?? null;
        if (is_array($location) && array_is_list($location)) {
            $location = $location[0] ?? null;
        }
        if (! is_array($location)) {
            return [null, null];
        }
        $address = $location['address'] ?? null;
        if (! is_array($address)) {
            return [null, null];
        }
        $city = $this->text($address['addressLocality'] ?? null);
        $state = $this->text($address['addressRegion'] ?? null);
        return [$city !== '' ? $city : null, $state !== '' ? $state : null];
    }

    private function experienceMonths(array $posting, string $body): ?int
    {
        $months = data_get($posting, 'experienceRequirements.monthsOfExperience');
        if (is_numeric($months)) {
            return max(0, (int) $months);
        }
        foreach ([
            '/Experience\s*:?\s*(\d+)\s*(?:\+)?\s*Years?/i',
            '/Minimum\s+(\d+)\s+(?:years?|yrs?)/i',
            '/(\d+)\s*(?:to|-)\s*\d+\s+years?\s+of\s+experience/i',
        ] as $pattern) {
            if (preg_match($pattern, $body, $match) === 1) {
                return max(0, (int) $match[1] * 12);
            }
        }
        return null;
    }

    private function salaryFromPosting(array $posting): array
    {
        $value = data_get($posting, 'baseSalary.value');
        $currency = $this->text(data_get($posting, 'baseSalary.currency'));
        if (! is_array($value)) {
            return [null, null, $currency !== '' ? Str::limit($currency, 16, '') : null, null];
        }
        $min = $this->numeric($value['minValue'] ?? $value['value'] ?? null);
        $max = $this->numeric($value['maxValue'] ?? $value['value'] ?? null);
        $period = $this->text($value['unitText'] ?? null);
        return [$min, $max, $currency !== '' ? Str::limit($currency, 16, '') : null, $period !== '' ? Str::lower($period) : null];
    }

    private function listValue(mixed $value): array
    {
        if ($value === null || $value === '') {
            return [];
        }
        if (is_array($value)) {
            $items = [];
            foreach ($value as $item) {
                $items = array_merge($items, $this->listValue($item));
            }
            return array_values(array_unique(array_filter($items)));
        }
        $text = trim(strip_tags($this->text($value)));
        if ($text === '') {
            return [];
        }
        $parts = preg_split('/(?:\r?\n|;|•)+/', $text) ?: [$text];
        return array_values(array_filter(array_map(static fn (string $part): string => trim($part), $parts)));
    }

    private function text(mixed $value): string
    {
        if ($value === null) {
            return '';
        }
        if (is_string($value) || is_numeric($value)) {
            return trim((string) $value);
        }
        if (is_bool($value)) {
            return $value ? 'Yes' : 'No';
        }
        if (! is_array($value)) {
            return '';
        }
        foreach (['name', 'value', 'text', 'title'] as $key) {
            if (array_key_exists($key, $value)) {
                $text = $this->text($value[$key]);
                if ($text !== '') {
                    return $text;
                }
            }
        }
        $parts = [];
        foreach ($value as $item) {
            $text = $this->text($item);
            if ($text !== '') {
                $parts[] = $text;
            }
        }
        return implode(', ', array_values(array_unique($parts)));
    }

    private function numeric(mixed $value): ?float
    {
        if (is_numeric($value)) {
            return min((float) $value, 99999999.99);
        }
        $clean = preg_replace('/[^\d.]/', '', $this->text($value));
        return $clean !== '' && is_numeric($clean) ? min((float) $clean, 99999999.99) : null;
    }

    private function urlOrNull(mixed $value): ?string
    {
        $url = $this->text($value);
        if ($url === '' || filter_var($url, FILTER_VALIDATE_URL) === false) {
            return null;
        }
        return in_array(Str::lower((string) parse_url($url, PHP_URL_SCHEME)), ['http', 'https'], true) ? $url : null;
    }
}
