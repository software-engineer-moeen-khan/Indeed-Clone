<?php

namespace App\Models;

use Abbasudo\Purity\Traits\Filterable;
use App\Models\Scopes\JobListingScope;
use App\Observers\JobListingObserver;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Attributes\ScopedBy;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\MassPrunable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Laravel\Scout\Searchable;

#[ObservedBy([JobListingObserver::class])]
#[ScopedBy([JobListingScope::class])]
class JobListing extends Model
{
    use Filterable, HasFactory, MassPrunable, Searchable;

    protected $fillable = [
        'job_id',
        'uuid',
        'employer_name',
        'employer_logo',
        'employer_website',
        'employer_company_type',
        'publisher',
        'employment_type',
        'job_title',
        'slug',
        'job_category',
        'category_image',
        'apply_link',
        'description',
        'is_remote',
        'city',
        'state',
        'country',
        'latitude',
        'longitude',
        'google_link',
        'posted_at',
        'expired_at',
        'min_salary',
        'max_salary',
        'salary_currency',
        'salary_period',
        'benefits',
        'qualifications',
        'responsibilities',
        'skills',
        'required_experience',
    ];

    protected function casts(): array
    {
        return [
            'posted_at' => 'datetime',
            'expired_at' => 'datetime',
            'required_experience' => 'integer',
            'qualifications' => 'array',
            'benefits' => 'array',
            'responsibilities' => 'array',
            'skills' => 'array',
            'is_remote' => 'boolean',
            'latitude' => 'float',
            'longitude' => 'float',
        ];
    }

    protected function description(): Attribute
    {
        return Attribute::make(
            get: fn (?string $value): ?string => $this->cleanImportedDescription($value),
            set: fn (?string $value): ?string => $this->cleanImportedDescription($value),
        );
    }

    private function cleanImportedDescription(?string $value): ?string
    {
        if ($value === null || trim($value) === '') {
            return $value;
        }

        $isNjp =
            str_contains((string) $this->publisher, 'National Jobs Portal') ||
            stripos($value, 'Source: National Jobs Portal (NJP)') !== false ||
            stripos($value, 'Toggle Job Description Read More / Less') !== false ||
            stripos($value, 'isDescriptionExpanded') !== false ||
            stripos($value, 'toggle-description-btn') !== false ||
            stripos($value, "info@njp.gov.pk") !== false;

        if (! $isNjp) {
            return $value;
        }

        $value = html_entity_decode($value, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $value = str_replace("\u{00A0}", ' ', $value);

        $eligibilityPos = stripos($value, 'Eligibility Criteria');
        $startPos = false;

        if ($eligibilityPos !== false) {
            $beforeEligibility = substr($value, 0, $eligibilityPos);
            $startPos = strripos($beforeEligibility, 'Job Description');
        }

        if ($startPos === false) {
            $cutoffCandidates = [];

            foreach ([
                'Toggle Job Description Read More / Less',
                'Quick Overview',
                'About Employer',
                'Source: National Jobs Portal (NJP)',
            ] as $marker) {
                $position = stripos($value, $marker);

                if ($position !== false) {
                    $cutoffCandidates[] = $position;
                }
            }

            $searchArea = $cutoffCandidates !== []
                ? substr($value, 0, min($cutoffCandidates))
                : $value;

            $startPos = strripos($searchArea, 'Job Description');
        }

        if ($startPos !== false) {
            $contentStart = $startPos + strlen('Job Description');
            $endCandidates = [];

            foreach ([
                'Eligibility Criteria',
                'Quick Overview',
                'About Employer',
                'Toggle Job Description Read More / Less',
                'Source: National Jobs Portal (NJP)',
            ] as $marker) {
                $position = stripos($value, $marker, $contentStart);

                if ($position !== false && $position > $contentStart) {
                    $endCandidates[] = $position;
                }
            }

            $contentEnd = $endCandidates !== [] ? min($endCandidates) : strlen($value);
            $candidate = trim(substr($value, $contentStart, $contentEnd - $contentStart));

            if (mb_strlen($candidate) >= 20) {
                $value = $candidate;
            }
        }

        foreach ([
            'Toggle Job Description Read More / Less',
            'isDescriptionExpanded',
            'toggle-description-btn',
            "document.addEventListener('DOMContentLoaded'",
            'document.addEventListener("DOMContentLoaded"',
        ] as $marker) {
            $position = stripos($value, $marker);

            if ($position !== false) {
                $value = substr($value, 0, $position);
            }
        }

        $value = preg_replace('/\bRead More\b\s*$/i', '', $value) ?? $value;
        $value = preg_replace('/[ \t]+/', ' ', $value) ?? $value;
        $value = preg_replace('/\R{3,}/', "\n\n", $value) ?? $value;
        $value = trim($value, " \t\n\r\0\x0B:-");

        if ($value !== '' && stripos($value, 'Source: National Jobs Portal (NJP)') === false) {
            $value .= "\n\nSource: National Jobs Portal (NJP)";
        }

        return $value;
    }

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'job_user', 'job_id', 'user_id')
            ->withTimestamps();
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(JobCategory::class, 'job_category', 'id');
    }

    public function applyOptions(): HasMany
    {
        return $this->hasMany(JobApplyOption::class);
    }

    public function getApplyOptionsAttribute(): array
    {
        return $this->applyOptions()->orderBy('publisher', 'desc')->get()->map(function ($option) {
            return [
                'publisher' => $option->publisher,
                'apply_link' => $option->apply_link,
                'is_direct' => $option->is_direct,
            ];
        })->toArray();
    }

    public function getFormattedSalaryAttribute(): ?string
    {
        $hasMin = $this->min_salary !== null && $this->min_salary !== '';
        $hasMax = $this->max_salary !== null && $this->max_salary !== '';

        if (! $hasMin && ! $hasMax) {
            return null;
        }

        $currency = trim((string) $this->salary_currency);

        $formatAmount = static function ($value) use ($currency): string {
            $numericValue = (float) $value;
            $decimals = floor($numericValue) === $numericValue ? 0 : 2;
            $amount = number_format($numericValue, $decimals);

            if ($currency === '') {
                return $amount;
            }

            $isSymbol = preg_match('/^[^\p{L}\p{N}]+$/u', $currency) === 1;

            return $isSymbol
                ? $currency.$amount
                : $currency.' '.$amount;
        };

        if ($hasMin && $hasMax) {
            $salary = $formatAmount($this->min_salary).' - '.$formatAmount($this->max_salary);
        } elseif ($hasMin) {
            $salary = 'From '.$formatAmount($this->min_salary);
        } else {
            $salary = 'Up to '.$formatAmount($this->max_salary);
        }

        if ($this->salary_period) {
            $salary .= ' / '.trim((string) $this->salary_period);
        }

        return $salary;
    }

    public function prunable(): Builder
    {
        return static::query()->where('created_at', '<=', now()->subDays(14));
    }

    public function toSearchableArray(): array
    {
        $array = $this->toArray();

        if (isset($array['posted_at']) && $array['posted_at']) {
            $array['posted_at'] = $this->posted_at->timestamp;
        } else {
            $array['posted_at'] = $this->created_at->timestamp;
        }

        if (isset($array['expired_at']) && $array['expired_at']) {
            $array['expired_at'] = $this->expired_at->timestamp;
        }

        return array_merge($array, [
            'id' => (string) $this->id,
            'created_at' => $this->created_at->timestamp,
            'job_category' => (string) $this->job_category,
            'is_remote' => (bool) $this->is_remote,
            'publisher' => (string) $this->publisher,
            'salary_min' => (int) $this->min_salary,
            'salary_max' => (int) $this->max_salary,
            'salary_currency' => (string) $this->salary_currency,
            'salary_period' => (string) $this->salary_period,
        ]);
    }

    public function searchableAs(): string
    {
        return 'listing_index';
    }

    public function package(): \Illuminate\Database\Eloquent\Relations\HasOne
    {
        return $this->hasOne(\Geezap\ContentFormatter\Models\Package::class, 'metadata->job_listing_id', 'id');
    }
}
