@extends('v2.layouts.app')

@section('content')
<section class="job-results-page">
    <div class="border-b border-[#e4e2e0] bg-white">
        <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 py-6">
            <form action="{{ route('job.index') }}" method="GET" class="grid grid-cols-1 md:grid-cols-[1fr_0.75fr_auto] gap-3 items-end">
                <div class="indeed-search-field">
                    <label for="jobs-search">What</label>
                    <i class="las la-search"></i>
                    <input
                        id="jobs-search"
                        type="text"
                        name="search"
                        value="{{ request('search') }}"
                        class="indeed-input"
                        placeholder="Job title, keywords, or company"
                    >
                </div>

                <div class="indeed-search-field">
                    <label for="jobs-location">Where</label>
                    <i class="las la-map-marker-alt"></i>
                    <input
                        id="jobs-location"
                        type="text"
                        name="location"
                        value="{{ request('location') }}"
                        class="indeed-input"
                        placeholder="City or location"
                    >
                </div>

                <button type="submit" class="btn-primary min-h-[52px] px-7">Find jobs</button>
            </form>
        </div>
    </div>

    <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 py-8 sm:py-10">
        <div class="mb-6 flex flex-col sm:flex-row sm:items-end sm:justify-between gap-2">
            <div>
                <h1 class="text-2xl sm:text-3xl font-bold text-[#2d2d2d]">Jobs</h1>
                <p class="mt-1 text-[#595959]">
                    <span id="job-count">{{ number_format($jobs->total()) }}</span> opportunities available
                </p>
            </div>
            <a href="{{ route('job.categories') }}" class="indeed-link">Browse by category</a>
        </div>

        <livewire:job-filter wire:key="job-filter-main" />
    </div>
</section>
@endsection

@push('extra-js')
<script>
    function updateJobCountDisplay(count) {
        const jobCountElement = document.getElementById('job-count');
        if (jobCountElement) {
            jobCountElement.textContent = Number(count).toLocaleString();
        }
        localStorage.setItem('last_job_count', count);
    }

    document.addEventListener('livewire:initialized', () => {
        Livewire.on('jobCountUpdated', (count) => updateJobCountDisplay(count));
    });

    document.addEventListener('visibilitychange', () => {
        if (document.visibilityState !== 'visible') return;

        const lastCount = localStorage.getItem('last_job_count');
        if (lastCount) updateJobCountDisplay(lastCount);

        const livewireElement = document.querySelector('[wire\\:id]');
        if (!livewireElement || typeof window.Livewire === 'undefined') return;

        const component = Livewire.find(livewireElement.getAttribute('wire:id'));
        if (component) component.$refresh();
    });
</script>
@endpush
