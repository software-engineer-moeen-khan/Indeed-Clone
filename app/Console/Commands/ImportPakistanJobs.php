<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\Country;
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

class ImportPakistanJobs extends Command
{
    protected $signature = 'jobs:import-pakistan
                            {--limit=10 : Maximum jobs to sync per Pakistan category}
                            {--category= : Import only one category name or slug}';

    protected $description = 'Create Pakistan job categories and import latest non-expired jobs from the Government of Pakistan National Jobs Portal.';

    private const SOURCE = 'National Jobs Portal (NJP)';
    private const BASE_URL = 'https://www.njp.gov.pk';

    private array $htmlCache = [];
    private array $jobCache = [];
    private int $requestCount = 0;

    /** @var array<string, array<string, mixed>> */
    private array $categoryDefinitions = [
        'biomedical-engineering' => [
            'name' => 'Biomedical Engineering',
            'query_name' => 'Biomedical Engineer',
            'queries' => ['biomedical', 'biomedical engineer', 'clinical engineer', 'medical equipment'],
            'keywords' => ['biomedical', 'bioengineer', 'clinical engineer', 'medical equipment', 'medical device'],
            'image' => 'assets/images/categories/biomedical.svg',
        ],
        'healthcare-medical' => [
            'name' => 'Healthcare & Medical',
            'query_name' => 'Healthcare Medical',
            'queries' => ['medical', 'health', 'hospital', 'doctor', 'nurse'],
            'keywords' => ['medical', 'health', 'hospital', 'doctor', 'nurse', 'pharmacy', 'pharmacist', 'clinical'],
            'image' => 'assets/images/categories/biomedical.svg',
        ],
        'information-technology-ai' => [
            'name' => 'Information Technology & AI',
            'query_name' => 'Information Technology AI Software',
            'queries' => ['information technology', 'software', 'artificial intelligence', 'cyber', 'network'],
            'keywords' => ['information technology', 'software', 'artificial intelligence', 'machine learning', 'cyber', 'network', 'computer', 'developer', 'technology', 'digital'],
            'image' => 'assets/images/categories/jobs.svg',
        ],
        'data-analytics' => [
            'name' => 'Data & Analytics',
            'query_name' => 'Data Analytics',
            'queries' => ['data', 'analytics', 'analyst', 'business intelligence'],
            'keywords' => ['data analyst', 'data engineer', 'data science', 'analytics', 'business intelligence', 'statistical', 'statistics'],
            'image' => 'assets/images/categories/jobs.svg',
        ],
        'engineering-technical' => [
            'name' => 'Engineering & Technical',
            'query_name' => 'Engineering Technical',
            'queries' => ['engineer', 'engineering', 'technical', 'quantity engineer'],
            'keywords' => ['engineer', 'engineering', 'technical', 'electrical', 'mechanical', 'civil', 'quantity', 'infrastructure', 'telecom'],
            'image' => 'assets/images/categories/jobs.svg',
        ],
        'finance-accounts' => [
            'name' => 'Finance & Accounts',
            'query_name' => 'Finance Accounts',
            'queries' => ['finance', 'accounts', 'accounting', 'audit'],
            'keywords' => ['finance', 'accounts', 'accounting', 'accountant', 'audit', 'auditor', 'treasury', 'financial'],
            'image' => 'assets/images/categories/jobs.svg',
        ],
        'banking-business-development' => [
            'name' => 'Banking & Business Development',
            'query_name' => 'Banking Business Development',
            'queries' => ['bank', 'relationship manager', 'business development', 'commercial'],
            'keywords' => ['bank', 'banking', 'relationship manager', 'business development', 'commercial', 'credit', 'portfolio'],
            'image' => 'assets/images/categories/jobs.svg',
        ],
        'administration-hr' => [
            'name' => 'Administration & HR',
            'query_name' => 'Administration Human Resources',
            'queries' => ['admin', 'human resources', 'HR', 'recruitment'],
            'keywords' => ['administration', 'administrative', 'human resources', ' hr ', 'recruitment', 'talent acquisition', 'office management'],
            'image' => 'assets/images/categories/jobs.svg',
        ],
        'legal-compliance' => [
            'name' => 'Legal & Compliance',
            'query_name' => 'Legal Compliance',
            'queries' => ['legal', 'law', 'compliance', 'regulatory'],
            'keywords' => ['legal', 'lawyer', 'counsel', 'law ', 'compliance', 'regulatory', 'regulation'],
            'image' => 'assets/images/categories/jobs.svg',
        ],
        'sales-marketing' => [
            'name' => 'Sales & Marketing',
            'query_name' => 'Sales Marketing',
            'queries' => ['sales', 'marketing', 'communications', 'media'],
            'keywords' => ['sales', 'marketing', 'communications', 'communication', 'media', 'brand', 'business development'],
            'image' => 'assets/images/categories/jobs.svg',
        ],
        'project-program-management' => [
            'name' => 'Project & Program Management',
            'query_name' => 'Project Program Management',
            'queries' => ['project', 'program', 'coordinator', 'planning', 'monitoring'],
            'keywords' => ['project', 'program', 'programme', 'coordinator', 'planning', 'monitoring', 'pmu', 'project management'],
            'image' => 'assets/images/categories/jobs.svg',
        ],
    ];

    public function handle(): int
    {
        config()->set('scout.driver', 'database');

        $limit = max(1, min(20, (int) $this->option('limit')));
        $pakistan = Country::query()->updateOrCreate(
            ['code' => 'PK'],
            ['name' => 'Pakistan', 'is_active' => true]
        );

        $categories = $this->ensureCategories($pakistan);
        $onlyCategory = Str::lower(trim((string) $this->option('category')));

        if ($onlyCategory !== '') {
            $categories = $categories->filter(function (JobCategory $category) use ($onlyCategory): bool {
                return in_array($onlyCategory, [
                    Str::lower((string) $category->name),
                    Str::lower((string) $category->slug),
                    Str::lower((string) $category->query_name),
                ], true);
            })->values();
        }

        if ($categories->isEmpty()) {
            $this->error('No matching Pakistan job category found.');

            return self::FAILURE;
        }

        $this->info(sprintf(
            'Importing up to %d latest Pakistan jobs for %d categor%s from NJP. Biomedical Engineering is processed first.',
            $limit,
            $categories->count(),
            $categories->count() === 1 ? 'y' : 'ies'
        ));

        $summary = [];
        $globalSeen = [];
        $total = 0;

        foreach ($categories as $category) {
            $this->newLine();
            $this->components->info("{$category->name}: finding latest Pakistan jobs");

            $definition = $this->categoryDefinitions[(string) $category->slug] ?? null;

            if (! is_array($definition)) {
                $summary[] = [$category->name, 0, $limit, 'No importer definition'];
                continue;
            }

            $links = $this->linksForQueries($definition['queries'] ?? []);
            $candidates = [];

            foreach ($links as $link) {
                if (isset($globalSeen[$link])) {
                    continue;
                }

                $job = $this->jobCache[$link] ??= $this->fetchNjpJob($link);

                if ($job === null || ! $this->matchesDefinition($job, $definition)) {
                    continue;
                }

                if ($job['expired_at'] instanceof Carbon && $job['expired_at']->lt(now()->startOfDay())) {
                    continue;
                }

                $candidates[] = $job;

                if (count($candidates) >= ($limit * 3)) {
                    break;
                }
            }

            usort(
                $candidates,
                static fn (array $a, array $b): int =>
                    ($b['posted_at']?->getTimestamp() ?? 0) <=> ($a['posted_at']?->getTimestamp() ?? 0)
            );

            $synced = 0;

            foreach ($candidates as $candidate) {
                if ($synced >= $limit) {
                    break;
                }

                $applyLink = (string) $candidate['apply_link'];
                $existing = JobListing::query()->where('apply_link', $applyLink)->first();

                if ($existing && (int) $existing->job_category !== (int) $category->id) {
                    $globalSeen[$applyLink] = true;
                    continue;
                }

                $candidate['job_category'] = $category->id;

                if ($existing) {
                    $existing->fill($candidate);
                    $existing->save();
                } else {
                    JobListing::query()->create($candidate);
                }

                $globalSeen[$applyLink] = true;
                $synced++;
                $total++;
            }

            $summary[] = [
                $category->name,
                $synced,
                $limit,
                self::SOURCE,
            ];

            if ($synced < $limit) {
                $this->components->warn("{$category->name}: {$synced}/{$limit} current matching NJP jobs found.");
            } else {
                $this->components->info("{$category->name}: {$synced}/{$limit} jobs synced");
            }
        }

        foreach (['jobCategories', 'jobCategoriesJobsCount', 'jobCategoriesAll'] as $cacheKey) {
            Cache::forget($cacheKey);
        }

        $this->newLine();
        $this->table(['Category', 'Synced', 'Target', 'Source'], $summary);
        $this->info("Done. {$total} latest Pakistan job slots synced using {$this->requestCount} NJP requests.");
        $this->line('Apply Now points to the real NJP job page, where the candidate can log in and apply.');

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

    private function linksForQueries(array $queries): array
    {
        $links = [];

        foreach ($queries as $query) {
            $query = trim((string) $query);

            if ($query === '') {
                continue;
            }

            try {
                $html = $this->fetchHtml(self::BASE_URL.'/jobs/search?q='.rawurlencode($query));
            } catch (Throwable $e) {
                $this->components->warn("NJP search '{$query}' failed: {$e->getMessage()}");
                continue;
            }

            foreach ($this->extractJobLinks($html) as $link) {
                $links[$link] = true;
            }
        }

        if ($links === []) {
            try {
                foreach ($this->extractJobLinks($this->fetchHtml(self::BASE_URL.'/jobs/live')) as $link) {
                    $links[$link] = true;
                }
            } catch (Throwable $e) {
                $this->components->warn("NJP live jobs page failed: {$e->getMessage()}");
            }
        }

        return array_keys($links);
    }

    private function extractJobLinks(string $html): array
    {
        preg_match_all(
            '~href\s*=\s*["\']([^"\']*/jobs/\d+(?:[^"\']*)?)["\']~i',
            $html,
            $matches
        );

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

    private function fetchNjpJob(string $url): ?array
    {
        try {
            $html = $this->fetchHtml($url);
        } catch (Throwable $e) {
            $this->components->warn("NJP job page failed ({$url}): {$e->getMessage()}");

            return null;
        }

        $posting = $this->extractStructuredJobPosting($html);
        $bodyText = $this->bodyText($html);

        $title = $this->text($posting['title'] ?? null);

        if ($title === '') {
            $title = $this->firstHeading($html);
        }

        if ($title === '') {
            return null;
        }

        $company = $this->text(data_get($posting, 'hiringOrganization.name'));

        if ($company === '') {
            $company = $this->companyFromBody($title, $bodyText) ?: 'Government of Pakistan';
        }

        $description = $this->text($posting['description'] ?? null);

        if ($description === '') {
            $description = $this->metaContent($html, 'description');
        }

        if (mb_strlen(strip_tags($description)) < 80) {
            $description = $bodyText;
        }

        $postedAt = $this->parseDate($posting['datePosted'] ?? null)
            ?? $this->dateFromBody($bodyText, ['Posted']);
        $expiredAt = $this->parseDate($posting['validThrough'] ?? null)
            ?? $this->dateFromBody($bodyText, ['Application Deadline', 'Deadline', 'Available Till', 'Available till']);

        $employmentType = $this->normalizeEmploymentType(
            $this->text($posting['employmentType'] ?? null).' '.$bodyText
        );

        [$city, $state] = $this->locationFromPosting($posting);
        $experienceMonths = $this->experienceMonths($posting, $bodyText);

        [$minSalary, $maxSalary, $currency, $period] = $this->salaryFromPosting($posting);

        $qualifications = $this->listValue(
            $posting['qualifications'] ?? $posting['educationRequirements'] ?? null
        );
        $responsibilities = $this->listValue($posting['responsibilities'] ?? null);
        $benefits = $this->listValue($posting['jobBenefits'] ?? null);

        $cleanDescription = $this->cleanDescription($description);
        $cleanDescription = trim($cleanDescription."\n\nSource: ".self::SOURCE);

        return [
            'employer_name' => Str::limit($company, 250, ''),
            'employer_logo' => $this->validUrl($this->text(data_get($posting, 'hiringOrganization.logo')))
                ? $this->text(data_get($posting, 'hiringOrganization.logo'))
                : null,
            'employer_website' => $this->validUrl($this->text(data_get($posting, 'hiringOrganization.sameAs')))
                ? $this->text(data_get($posting, 'hiringOrganization.sameAs'))
                : null,
            'employer_company_type' => 'Government / Public Sector',
            'publisher' => self::SOURCE,
            'employment_type' => $employmentType,
            'job_title' => Str::limit($title, 250, ''),
            'apply_link' => $url,
            'description' => Str::limit($cleanDescription, 60000, ''),
            'is_remote' => false,
            'city' => $city,
            'state' => $state,
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
            'required_experience' => $experienceMonths,
        ];
    }

    private function matchesDefinition(array $job, array $definition): bool
    {
        $haystack = ' '.Str::lower(
            $this->text($job['job_title'] ?? null).' '.$this->text($job['description'] ?? null)
        ).' ';

        foreach ($definition['keywords'] ?? [] as $keyword) {
            $keyword = Str::lower(trim((string) $keyword));

            if ($keyword !== '' && str_contains($haystack, $keyword)) {
                return true;
            }
        }

        return false;
    }

    private function fetchHtml(string $url): string
    {
        if (isset($this->htmlCache[$url])) {
            return $this->htmlCache[$url];
        }

        $this->requestCount++;

        $html = $this->http()
            ->get($url)
            ->throw()
            ->body();

        return $this->htmlCache[$url] = $html;
    }

    private function http(): PendingRequest
    {
        return Http::withHeaders([
                'Accept' => 'text/html,application/xhtml+xml',
                'Accept-Language' => 'en-US,en;q=0.9',
            ])
            ->withUserAgent('Mozilla/5.0 (compatible; BestWayJobs/1.0; +Pakistan-job-import)')
            ->timeout(30)
            ->connectTimeout(10)
            ->retry([500, 1200], throw: false);
    }

    private function extractStructuredJobPosting(string $html): array
    {
        preg_match_all(
            '~<script[^>]*type=["\']application/ld\+json["\'][^>]*>(.*?)</script>~is',
            $html,
            $matches
        );

        foreach ($matches[1] ?? [] as $json) {
            $decoded = json_decode(
                html_entity_decode(trim((string) $json), ENT_QUOTES | ENT_HTML5, 'UTF-8'),
                true
            );

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
        $types = is_array($type) ? $type : [$type];

        foreach ($types as $candidateType) {
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

        if (preg_match($pattern, $body, $match) !== 1) {
            return '';
        }

        return trim(preg_replace('/\s+/', ' ', $match[1]) ?? $match[1]);
    }

    private function metaContent(string $html, string $name): string
    {
        $quotedName = preg_quote($name, '~');

        if (preg_match(
            '~<meta[^>]+(?:name|property)=["\']'.$quotedName.'["\'][^>]+content=["\']([^"\']*)["\'][^>]*>~i',
            $html,
            $match
        ) !== 1) {
            return '';
        }

        return trim(html_entity_decode($match[1], ENT_QUOTES | ENT_HTML5, 'UTF-8'));
    }

    private function dateFromBody(string $body, array $labels): ?Carbon
    {
        foreach ($labels as $label) {
            $label = preg_quote($label, '/');

            if (preg_match('/'.$label.'\s*:?\s*(\d{1,2}\s+[A-Za-z]{3,9}\s+\d{4})/i', $body, $match) === 1) {
                return $this->parseDate($match[1]);
            }
        }

        return null;
    }

    private function parseDate(mixed $value): ?Carbon
    {
        $value = $this->text($value);

        if ($value === '') {
            return null;
        }

        try {
            return Carbon::parse($value);
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

        if (
            str_contains($value, 'contract') ||
            str_contains($value, 'intern') ||
            str_contains($value, 'temporary') ||
            str_contains($value, 'consultant')
        ) {
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

        return [
            $city !== '' ? Str::limit($city, 250, '') : null,
            $state !== '' ? Str::limit($state, 250, '') : null,
        ];
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
            return [null, null, $currency !== '' ? $currency : null, null];
        }

        $min = $this->numeric($value['minValue'] ?? $value['value'] ?? null);
        $max = $this->numeric($value['maxValue'] ?? $value['value'] ?? null);
        $period = $this->text($value['unitText'] ?? null);

        return [
            $min,
            $max,
            $currency !== '' ? Str::limit($currency, 16, '') : null,
            $period !== '' ? Str::lower($period) : null,
        ];
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

        return array_values(array_filter(array_map(
            static fn (string $part): string => trim($part),
            $parts
        )));
    }

    private function cleanDescription(string $description): string
    {
        $plain = html_entity_decode(strip_tags($description), ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $plain = preg_replace('/[ \t]+/', ' ', $plain) ?? $plain;
        $plain = preg_replace('/\R{3,}/', "\n\n", $plain) ?? $plain;

        return Str::limit(trim($plain), 59800, '');
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

        return $clean !== '' && is_numeric($clean)
            ? min((float) $clean, 99999999.99)
            : null;
    }

    private function validUrl(string $url): bool
    {
        return $url !== ''
            && filter_var($url, FILTER_VALIDATE_URL) !== false
            && in_array(Str::lower((string) parse_url($url, PHP_URL_SCHEME)), ['http', 'https'], true);
    }
}
