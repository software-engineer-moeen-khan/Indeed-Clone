@extends('v2.layouts.app')

@section('content')
<section class="indeed-home-hero py-12 sm:py-16 lg:py-20">
    <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="text-center max-w-3xl mx-auto">
            <h1 class="text-3xl sm:text-4xl lg:text-5xl font-extrabold tracking-tight text-[#2d2d2d] leading-tight">
                Find jobs that fit your life
            </h1>
            <p class="mt-4 text-base sm:text-lg text-[#595959]">
                Search {{ number_format($availableJobs) }} opportunities from companies hiring now.
            </p>
        </div>

        <form action="{{ route('job.index') }}" method="GET" class="indeed-search-shell mt-8 sm:mt-10">
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-[1.25fr_0.8fr_0.8fr_auto] gap-3 items-end">
                <div class="indeed-search-field">
                    <label for="home-search">What</label>
                    <i class="las la-search"></i>
                    <input
                        id="home-search"
                        name="search"
                        type="text"
                        class="indeed-input"
                        value="{{ request('search') }}"
                        placeholder="Job title, keywords, or company"
                        autocomplete="off"
                    >
                </div>

                <div class="indeed-search-field">
                    <label for="home-city">City</label>
                    <i class="las la-city"></i>
                    <input
                        id="home-city"
                        name="city"
                        type="text"
                        class="indeed-input"
                        value="{{ request('city') }}"
                        placeholder="e.g. Lahore"
                        autocomplete="off"
                    >
                </div>

                <div class="indeed-search-field">
                    <label for="home-country">Country</label>
                    <i class="las la-globe"></i>
                    <select id="home-country" name="country" class="indeed-input appearance-none">
                        <option value="">All countries</option>
                        @foreach($searchCountries as $country)
                            <option value="{{ $country->code }}" @selected(request('country') === $country->code)>{{ $country->name }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="indeed-search-button-wrap">
                    <button type="submit" class="btn-primary px-7 min-h-[52px]">
                        Find jobs
                    </button>
                </div>
            </div>
        </form>

        @if($popularSearches->isNotEmpty())
            <div class="mt-5 flex flex-wrap items-center justify-center gap-x-5 gap-y-2 text-sm">
                <span class="text-[#595959]">Popular searches:</span>
                @foreach($popularSearches as $popularSearch)
                    <a class="indeed-link" href="{{ route('job.index', ['search' => $popularSearch->search_query]) }}">
                        {{ $popularSearch->label }}
                    </a>
                @endforeach
            </div>
        @endif
    </div>
</section>

@guest
<section class="py-8 border-b border-[#e4e2e0] bg-white">
    <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="indeed-card px-6 py-7 sm:px-8 sm:py-8 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-5">
            <div>
                <h2 class="text-xl sm:text-2xl font-bold text-[#2d2d2d]">Your next job starts here</h2>
                <p class="mt-1.5 text-[#595959]">Create an account to keep your profile and applications in one place.</p>
            </div>
            <div class="flex flex-wrap gap-3 shrink-0">
                <a href="{{ route('register') }}" class="btn-primary">Create account</a>
                <a href="{{ route('login') }}" class="btn-secondary">Sign in</a>
            </div>
        </div>
    </div>
</section>
@endguest

<section class="py-10 sm:py-14 bg-[#f7f7f7] border-b border-[#e4e2e0]">
    <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex items-end justify-between gap-4 mb-6">
            <div>
                <h2 class="text-2xl sm:text-3xl font-bold text-[#2d2d2d]">Latest jobs</h2>
                <p class="mt-1 text-[#595959]">Fresh opportunities recently added to Best Way Jobs.</p>
            </div>
            <a href="{{ route('job.index') }}" class="indeed-link hidden sm:inline">View all jobs</a>
        </div>

        @if($latestJobs->isEmpty())
            <div class="indeed-card p-8 text-center text-[#595959]">No jobs are available right now.</div>
        @else
            <div class="home-job-grid">
                @foreach($latestJobs->take(6) as $job)
                    <x-v2.home.job-card :job="$job" />
                @endforeach
            </div>
        @endif

        <div class="mt-6 sm:hidden">
            <a href="{{ route('job.index') }}" class="btn-secondary w-full">View all jobs</a>
        </div>
    </div>
</section>

<section class="py-10 sm:py-14 bg-white">
    <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex items-end justify-between gap-4 mb-6">
            <div>
                <h2 class="text-2xl sm:text-3xl font-bold text-[#2d2d2d]">Browse by category</h2>
                <p class="mt-1 text-[#595959]">Explore jobs by the type of work you want to do.</p>
            </div>
            <a href="{{ route('job.categories') }}" class="indeed-link hidden sm:inline">All categories</a>
        </div>

        <div class="home-category-grid">
            @foreach($jobCategories->take(8) as $category)
                <a href="{{ route('job.index', ['category' => $category->id]) }}" class="home-category-card group">
                    <div class="w-10 h-10 rounded-lg bg-[#eef4fb] text-[#2557a7] flex items-center justify-center mb-4">
                        <i class="las la-briefcase text-xl"></i>
                    </div>
                    <h3 class="font-bold text-[#2d2d2d] group-hover:text-[#2557a7]">{{ ucwords($category->name) }}</h3>
                    <p class="mt-1.5 text-sm text-[#595959]">{{ number_format($category->jobs_count ?? 0) }} {{ \Illuminate\Support\Str::plural('job', $category->jobs_count ?? 0) }}</p>
                </a>
            @endforeach
        </div>
    </div>
</section>

@if($mostViewedJobs->isNotEmpty())
<section class="py-10 sm:py-14 bg-[#f7f7f7] border-y border-[#e4e2e0]">
    <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="mb-6">
            <h2 class="text-2xl sm:text-3xl font-bold text-[#2d2d2d]">Popular jobs</h2>
            <p class="mt-1 text-[#595959]">Roles job seekers are viewing most often.</p>
        </div>
        <div class="home-job-grid">
            @foreach($mostViewedJobs->take(4) as $job)
                <x-v2.home.job-card :job="$job" />
            @endforeach
        </div>
    </div>
</section>
@endif

<section class="py-10 sm:py-12 bg-white">
    <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="grid grid-cols-2 lg:grid-cols-4 border border-[#d4d2d0] rounded-xl overflow-hidden bg-white">
            <div class="p-6 border-r border-b lg:border-b-0 border-[#e4e2e0] text-center">
                <div class="text-2xl sm:text-3xl font-bold text-[#2d2d2d]">{{ number_format($availableJobs) }}</div>
                <div class="mt-1 text-sm text-[#595959]">Active jobs</div>
            </div>
            <div class="p-6 lg:border-r border-b lg:border-b-0 border-[#e4e2e0] text-center">
                <div class="text-2xl sm:text-3xl font-bold text-[#2d2d2d]">{{ number_format($todayAddedJobsCount) }}</div>
                <div class="mt-1 text-sm text-[#595959]">Added today</div>
            </div>
            <div class="p-6 border-r border-[#e4e2e0] text-center">
                <div class="text-2xl sm:text-3xl font-bold text-[#2d2d2d]">{{ number_format($jobCategoriesCount) }}</div>
                <div class="mt-1 text-sm text-[#595959]">Categories</div>
            </div>
            <div class="p-6 text-center">
                <div class="text-2xl sm:text-3xl font-bold text-[#2d2d2d]">{{ number_format($lastWeekAddedJobsCount) }}</div>
                <div class="mt-1 text-sm text-[#595959]">Added this week</div>
            </div>
        </div>
    </div>
</section>
@endsection
