@extends('v2.layouts.app')

@section('content')
<section class="bg-white border-b border-[#e4e2e0]">
    <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 py-10 sm:py-12">
        <h1 class="text-3xl sm:text-4xl font-bold tracking-tight text-[#2d2d2d]">Browse job categories</h1>
        <p class="mt-2 max-w-2xl text-[#595959]">Choose a category to explore roles that match your interests and experience.</p>
    </div>
</section>

<section class="py-10 sm:py-12 bg-[#f7f7f7] min-h-[60vh]">
    <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8">
        @if($jobCategories->isEmpty())
            <div class="indeed-card p-8 text-center text-[#595959]">No categories found.</div>
        @else
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
                @foreach($jobCategories as $category)
                    <a href="{{ route('job.index', ['category' => $category->id]) }}"
                       class="indeed-card p-5 sm:p-6 group flex items-start gap-4">
                        <div class="w-11 h-11 shrink-0 rounded-lg bg-[#eef4fb] flex items-center justify-center text-[#2557a7]">
                            @if($category->category_image)
                                <img src="{{ url($category->category_image) }}" alt="{{ $category->name }}" class="w-6 h-6 object-contain" loading="lazy">
                            @else
                                <i class="las la-briefcase text-xl"></i>
                            @endif
                        </div>

                        <div class="min-w-0">
                            <h2 class="font-bold text-[#2d2d2d] group-hover:text-[#2557a7] text-lg leading-snug">
                                {{ ucwords($category->name) }}
                            </h2>
                            <p class="mt-1 text-sm text-[#595959]">
                                {{ number_format($category->jobs_count) }} {{ \Illuminate\Support\Str::plural('job', $category->jobs_count) }}
                            </p>
                            <span class="inline-flex items-center gap-1 mt-3 text-sm font-semibold text-[#2557a7]">
                                View jobs <i class="las la-arrow-right"></i>
                            </span>
                        </div>
                    </a>
                @endforeach
            </div>
        @endif
    </div>
</section>
@endsection
