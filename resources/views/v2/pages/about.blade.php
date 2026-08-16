@extends('v2.layouts.app')

@section('content')
<section class="bg-white border-b border-[#e4e2e0]">
    <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 py-12 sm:py-16">
        <div class="max-w-3xl">
            <p class="text-sm font-semibold text-[#2557a7] mb-3">ABOUT BEST WAY JOBS</p>
            <h1 class="text-3xl sm:text-4xl font-bold tracking-tight text-[#2d2d2d]">A simpler way to discover job opportunities</h1>
            <p class="mt-5 text-lg leading-8 text-[#595959]">
                Best Way Jobs is a job discovery platform designed to help job seekers search, filter, review and access relevant opportunities without unnecessary clutter.
            </p>
        </div>
    </div>
</section>

<section class="bg-[#f7f7f7]">
    <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 py-10 sm:py-14">
        <div class="grid gap-6 md:grid-cols-3">
            <div class="indeed-card p-6">
                <div class="w-10 h-10 rounded-lg bg-[#eef4fb] text-[#2557a7] flex items-center justify-center mb-4">
                    <i class="las la-search text-xl"></i>
                </div>
                <h2 class="text-lg font-bold text-[#2d2d2d]">Search clearly</h2>
                <p class="mt-2 text-sm leading-6 text-[#595959]">Search by job title, keywords, company, city, country, category and other practical filters.</p>
            </div>

            <div class="indeed-card p-6">
                <div class="w-10 h-10 rounded-lg bg-[#eef4fb] text-[#2557a7] flex items-center justify-center mb-4">
                    <i class="las la-briefcase text-xl"></i>
                </div>
                <h2 class="text-lg font-bold text-[#2d2d2d]">Compare opportunities</h2>
                <p class="mt-2 text-sm leading-6 text-[#595959]">Review employer, location, employment type, compensation and job details in a consistent format.</p>
            </div>

            <div class="indeed-card p-6">
                <div class="w-10 h-10 rounded-lg bg-[#eef4fb] text-[#2557a7] flex items-center justify-center mb-4">
                    <i class="las la-user-check text-xl"></i>
                </div>
                <h2 class="text-lg font-bold text-[#2d2d2d]">Stay organized</h2>
                <p class="mt-2 text-sm leading-6 text-[#595959]">Registered users can use profile and application-related features available on the platform.</p>
            </div>
        </div>
    </div>
</section>

<section class="bg-white">
    <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 py-10 sm:py-14">
        <div class="grid gap-10 lg:grid-cols-[1.4fr_0.8fr]">
            <div>
                <h2 class="text-2xl font-bold text-[#2d2d2d]">How Best Way Jobs works</h2>
                <div class="mt-6 space-y-6 text-[#595959] leading-7">
                    <div>
                        <h3 class="font-bold text-[#2d2d2d]">1. Find relevant roles</h3>
                        <p class="mt-1">Use search and filters to narrow job listings to the opportunities that fit what you are looking for.</p>
                    </div>
                    <div>
                        <h3 class="font-bold text-[#2d2d2d]">2. Review the details</h3>
                        <p class="mt-1">Open a listing to review the role, company information, location, requirements and available application options.</p>
                    </div>
                    <div>
                        <h3 class="font-bold text-[#2d2d2d]">3. Apply through the available link</h3>
                        <p class="mt-1">Some listings may direct you to an employer or third-party website. Those external websites operate under their own terms and privacy practices.</p>
                    </div>
                </div>
            </div>

            <aside class="indeed-card p-6 h-fit">
                <h2 class="text-lg font-bold text-[#2d2d2d]">What matters to us</h2>
                <ul class="mt-4 space-y-3 text-sm text-[#595959]">
                    <li class="flex gap-3"><i class="las la-check text-[#2557a7] mt-1"></i><span>Useful search and filtering</span></li>
                    <li class="flex gap-3"><i class="las la-check text-[#2557a7] mt-1"></i><span>Readable job information</span></li>
                    <li class="flex gap-3"><i class="las la-check text-[#2557a7] mt-1"></i><span>Simple, consistent navigation</span></li>
                    <li class="flex gap-3"><i class="las la-check text-[#2557a7] mt-1"></i><span>Respect for user information</span></li>
                </ul>
            </aside>
        </div>

        <div class="mt-12 border-t border-[#e4e2e0] pt-8 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div>
                <h2 class="text-xl font-bold text-[#2d2d2d]">Ready to explore opportunities?</h2>
                <p class="mt-1 text-[#595959]">Browse the latest jobs available on Best Way Jobs.</p>
            </div>
            <a href="{{ route('job.index') }}" class="btn-primary shrink-0">Find jobs</a>
        </div>
    </div>
</section>
@endsection