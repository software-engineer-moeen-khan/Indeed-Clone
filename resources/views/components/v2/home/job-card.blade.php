@props(['job'])

<article class="indeed-job-card">
    <div class="flex items-start justify-between gap-4">
        <div class="min-w-0 flex-1">
            <a href="{{ route('job.show', $job->slug) }}" class="indeed-job-card-title block">
                {{ $job->job_title }}
            </a>

            <div class="indeed-job-card-company">{{ $job->employer_name }}</div>

            <div class="indeed-job-card-location">
                @if($job->is_remote)
                    Remote
                    @if($job->country)
                        · {{ \App\Helpers\CountryFlag::getCountry($job->country) }}
                    @endif
                @else
                    {{ collect([$job->city, $job->state])->filter()->join(', ') ?: ($job->country ? \App\Helpers\CountryFlag::getCountry($job->country) : 'Location not specified') }}
                @endif
            </div>
        </div>

        <div class="shrink-0">
            <livewire:jobs.bookmark-job :jobId="$job->id" :key="'bookmark-'.$job->id" />
        </div>
    </div>

    <div class="mt-3 flex flex-wrap gap-2">
        @if($job->formatted_salary)
            <span class="indeed-chip">
                <i class="las la-money-bill-wave text-[#595959]"></i>
                {{ $job->formatted_salary }}
            </span>
        @endif

        @if($job->employment_type)
            <span class="indeed-chip">
                <i class="las la-briefcase text-[#595959]"></i>
                {{ str_replace('_', ' ', $job->employment_type) }}
            </span>
        @endif

        @if($job->category)
            <span class="indeed-chip">{{ $job->category->name }}</span>
        @endif
    </div>

    @if($job->description)
        <p class="indeed-job-card-description">
            {{ \Illuminate\Support\Str::limit(trim(strip_tags($job->description)), 180) }}
        </p>
    @endif

    <div class="indeed-job-card-footer">
        @if($job->posted_at)
            <span>Posted {{ $job->posted_at->diffForHumans() }}</span>
        @else
            <span>Posted {{ $job->created_at->diffForHumans() }}</span>
        @endif

        @if($job->publisher)
            <span>·</span>
            <span>{{ $job->publisher }}</span>
        @endif

        @if($job->views)
            <span>·</span>
            <span>{{ number_format($job->views) }} views</span>
        @endif
    </div>
</article>
