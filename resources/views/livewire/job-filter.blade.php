<div x-data="{ showMobileFilters: false }">
    <div class="lg:hidden mb-5">
        <button type="button"
                @click="showMobileFilters = !showMobileFilters"
                class="w-full flex items-center justify-between rounded-lg border border-[#d4d2d0] bg-white px-4 py-3 text-sm font-semibold text-[#2d2d2d]">
            <span class="flex items-center gap-2">
                <i class="las la-filter text-lg text-[#2557a7]"></i>
                Filters
                @if($this->getActiveFilterCount() > 0)
                    <span class="rounded-full bg-[#2557a7] px-2 py-0.5 text-xs text-white">{{ $this->getActiveFilterCount() }}</span>
                @endif
            </span>
            <i class="las la-angle-down" :class="{ 'rotate-180': showMobileFilters }"></i>
        </button>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-[260px_minmax(0,1fr)] gap-6 items-start">
        <aside class="lg:sticky lg:top-24"
               :class="showMobileFilters ? 'block' : 'hidden lg:block'">
            <div class="rounded-xl border border-[#d4d2d0] bg-white p-5 shadow-sm space-y-5">
                <div class="flex items-center justify-between gap-3">
                    <h2 class="font-bold text-[#2d2d2d]">Filter jobs</h2>
                    @if($this->getActiveFilterCount() > 0)
                        <button type="button" wire:click="clearAllFilters" class="text-sm font-semibold text-[#2557a7] hover:underline">
                            Clear all
                        </button>
                    @endif
                </div>

                <div>
                    <label for="filter-search" class="block text-sm font-semibold text-[#2d2d2d] mb-2">Keywords</label>
                    <input id="filter-search"
                           type="text"
                           wire:model.live.debounce.350ms="search"
                           class="indeed-input"
                           placeholder="Job title or company">
                </div>

                <div>
                    <label for="filter-city" class="block text-sm font-semibold text-[#2d2d2d] mb-2">City</label>
                    <div class="relative">
                        <i class="las la-city absolute left-3 top-1/2 -translate-y-1/2 text-[#767676]"></i>
                        <input id="filter-city"
                               type="text"
                               wire:model.live.debounce.350ms="city"
                               class="indeed-input !pl-10"
                               placeholder="e.g. Lahore">
                    </div>
                </div>

                <div>
                    <label for="filter-country" class="block text-sm font-semibold text-[#2d2d2d] mb-2">Country</label>
                    <select id="filter-country" wire:model.live="country" class="indeed-input appearance-none">
                        <option value="">All countries</option>
                        @foreach($countries as $code => $countryOption)
                            <option value="{{ $code }}">{{ $countryOption->name }}</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label for="filter-category" class="block text-sm font-semibold text-[#2d2d2d] mb-2">Category</label>
                    <select id="filter-category" wire:model.live="category" class="indeed-input appearance-none">
                        <option value="">All categories</option>
                        @foreach($categories as $categoryOption)
                            <option value="{{ $categoryOption->id }}">{{ $categoryOption->name }}</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="flex items-center gap-3 text-sm font-medium text-[#2d2d2d] cursor-pointer">
                        <input type="checkbox"
                               wire:model.live="remote"
                               class="rounded border-[#949494] text-[#2557a7] focus:ring-[#2557a7]">
                        Remote jobs only
                    </label>
                </div>

                <div>
                    <div class="text-sm font-semibold text-[#2d2d2d] mb-2">Job type</div>
                    <div class="space-y-2.5">
                        @foreach($jobTypes as $value => $label)
                            <label class="flex items-center gap-3 text-sm text-[#595959] cursor-pointer">
                                <input type="checkbox"
                                       wire:model.live="types"
                                       value="{{ $value }}"
                                       class="rounded border-[#949494] text-[#2557a7] focus:ring-[#2557a7]">
                                {{ $label }}
                            </label>
                        @endforeach
                    </div>
                </div>

                @if($publishers->isNotEmpty())
                    <div>
                        <label for="filter-source" class="block text-sm font-semibold text-[#2d2d2d] mb-2">Source</label>
                        <select id="filter-source" wire:model.live="source" class="indeed-input appearance-none">
                            <option value="">All sources</option>
                            @foreach($publishers as $publisherOption)
                                <option value="{{ $publisherOption }}">{{ $publisherOption }}</option>
                            @endforeach
                        </select>
                    </div>
                @endif
            </div>
        </aside>

        <section class="relative min-w-0">
            <div wire:loading.flex
                 wire:target="search,city,country,source,category,remote,types,clearAllFilters,loadMore"
                 class="absolute inset-0 z-20 items-start justify-center rounded-xl bg-white/80 pt-24 backdrop-blur-[1px]">
                <div class="flex items-center gap-3 rounded-lg border border-[#d4d2d0] bg-white px-4 py-3 shadow-sm text-sm font-semibold text-[#595959]">
                    <span class="h-5 w-5 animate-spin rounded-full border-2 border-[#2557a7] border-r-transparent"></span>
                    Updating jobs…
                </div>
            </div>

            <div class="mb-4 flex flex-wrap items-center justify-between gap-3">
                <p class="text-sm text-[#595959]">
                    <span class="font-bold text-[#2d2d2d]">{{ number_format($totalJobs) }}</span>
                    {{ \Illuminate\Support\Str::plural('job', $totalJobs) }} found
                    @if($city)
                        in <span class="font-semibold text-[#2d2d2d]">{{ $city }}</span>
                    @endif
                    @if($country && isset($countries[$country]))
                        {{ $city ? ',' : 'in' }} <span class="font-semibold text-[#2d2d2d]">{{ $countries[$country]->name }}</span>
                    @endif
                </p>

                @if($city || $country)
                    <div class="flex flex-wrap gap-2">
                        @if($city)
                            <button type="button" wire:click="$set('city', '')" class="indeed-chip hover:border-[#2557a7]">
                                City: {{ $city }} <i class="las la-times ml-1"></i>
                            </button>
                        @endif
                        @if($country && isset($countries[$country]))
                            <button type="button" wire:click="$set('country', '')" class="indeed-chip hover:border-[#2557a7]">
                                Country: {{ $countries[$country]->name }} <i class="las la-times ml-1"></i>
                            </button>
                        @endif
                    </div>
                @endif
            </div>

            <div class="space-y-4">
                @forelse($jobs as $job)
                    <x-v2.home.job-card :job="$job" />
                @empty
                    <div class="rounded-xl border border-[#d4d2d0] bg-white p-10 text-center shadow-sm">
                        <div class="mx-auto mb-4 flex h-12 w-12 items-center justify-center rounded-full bg-[#eef4fb] text-[#2557a7]">
                            <i class="las la-search text-2xl"></i>
                        </div>
                        <h3 class="text-lg font-bold text-[#2d2d2d]">No jobs found</h3>
                        <p class="mt-2 text-sm text-[#595959]">Try a different keyword, city, country, or clear the filters.</p>
                        @if($this->getActiveFilterCount() > 0)
                            <button type="button" wire:click="clearAllFilters" class="btn-secondary mt-5">Clear filters</button>
                        @endif
                    </div>
                @endforelse
            </div>

            @if($hasMorePages)
                <div class="mt-6 text-center">
                    <button type="button"
                            wire:click="loadMore"
                            wire:loading.attr="disabled"
                            wire:target="loadMore"
                            class="btn-secondary min-w-40">
                        <span wire:loading.remove wire:target="loadMore">Show more jobs</span>
                        <span wire:loading wire:target="loadMore">Loading…</span>
                    </button>
                </div>
            @endif
        </section>
    </div>
</div>
